<?php

namespace ItmoSoft\Returns\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use ItmoSoft\Returns\Events\ReturnRequested;
use ItmoSoft\Returns\Events\ReturnStatusChanged;
use ItmoSoft\Returns\Exceptions\InvalidReturnTransition;
use ItmoSoft\Returns\Models\ReturnItem;
use ItmoSoft\Returns\Models\ReturnRequest;
use ItmoSoft\Returns\Models\ReturnStatusHistory;
use InvalidArgumentException;

class ReturnManager
{
    private const TRANSITIONS = [
        'requested' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['received', 'cancelled'],
        'received' => ['inspected'], 'inspected' => ['completed'],
        'rejected' => [], 'completed' => [], 'cancelled' => [],
    ];

    public function request(array $data): ReturnRequest
    {
        if (empty($data['order_id'])) {
            throw ValidationException::withMessages(['order_id' => 'An order ID is required.']);
        }
        if (empty($data['items']) || ! is_array($data['items'])) {
            throw ValidationException::withMessages(['items' => 'At least one return item is required.']);
        }

        $modelClass = config('itmosoft-returns.order_items.model');
        $orderColumn = (string) config('itmosoft-returns.order_items.order_id_column', 'order_id');
        $quantityColumn = (string) config('itmosoft-returns.order_items.quantity_column', 'quantity');
        $priceColumn = (string) config('itmosoft-returns.order_items.unit_price_column', 'unit_price');
        $returnItemClass = config('itmosoft-returns.models.item', ReturnItem::class);
        if (! is_string($returnItemClass) || ! class_exists($returnItemClass) || ! is_subclass_of($returnItemClass, Model::class)) {
            throw new InvalidArgumentException('Configure itmosoft-returns.models.item to a valid Eloquent model.');
        }
        if (! is_string($modelClass) || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('Configure itmosoft-returns.order_items.model to your order-item Eloquent model before accepting returns.');
        }

        $quantities = [];
        foreach ($data['items'] as $index => $item) {
            $itemId = $item['order_item_id'] ?? null;
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            if (! $itemId || $quantity === false || $quantity < 1) {
                throw ValidationException::withMessages(["items.$index" => 'Each item requires an order item ID and a positive whole-number quantity.']);
            }
            $quantities[(string) $itemId] = ($quantities[(string) $itemId] ?? 0) + $quantity;
        }

        $return = DB::transaction(function () use ($data, $modelClass, $returnItemClass, $orderColumn, $quantityColumn, $priceColumn, $quantities): ReturnRequest {
            $itemModels = [];
            foreach ($quantities as $itemId => $requestedQuantity) {
                /** @var Model|null $orderItem */
                $orderItem = $modelClass::query()->whereKey($itemId)->where($orderColumn, $data['order_id'])->lockForUpdate()->first();
                if (! $orderItem) {
                    throw ValidationException::withMessages(['items' => "Order item [{$itemId}] does not belong to order [{$data['order_id']}]."]);
                }
                if (! array_key_exists($quantityColumn, $orderItem->getAttributes())) {
                    throw new InvalidArgumentException("Configured order item quantity column [{$quantityColumn}] does not exist.");
                }
                $purchased = (int) $orderItem->getAttribute($quantityColumn);
                $returnItem = new $returnItemClass();
                $returnItemTable = $returnItem->getTable();
                $returnRequestTable = (new ReturnRequest())->getTable();
                $reserved = (int) $returnItemClass::query()
                    ->join($returnRequestTable.' as rr', 'rr.id', '=', $returnItemTable.'.return_request_id')
                    ->where($returnItemTable.'.order_item_id', $itemId)
                    ->where('rr.order_id', $data['order_id'])
                    ->whereNotIn('rr.status', ['rejected', 'cancelled'])
                    ->sum($returnItemTable.'.quantity');
                if ($requestedQuantity + $reserved > $purchased) {
                    throw ValidationException::withMessages(['items' => "Return quantity for order item [{$itemId}] exceeds the unreturned purchased quantity."]);
                }
                $itemModels[$itemId] = $orderItem;
            }

            $return = ReturnRequest::query()->create([
                'order_id' => $data['order_id'], 'customer_type' => $data['customer_type'] ?? null,
                'customer_id' => $data['customer_id'] ?? null, 'reference' => $data['reference'] ?? $this->reference(),
                'status' => 'requested', 'reason' => $data['reason'] ?? 'other',
                'customer_note' => $data['customer_note'] ?? null, 'requested_at' => now(), 'metadata' => $data['metadata'] ?? null,
            ]);
            foreach ($data['items'] as $item) {
                $orderItem = $itemModels[(string) $item['order_item_id']];
                $unitPrice = $orderItem->getAttribute($priceColumn);
                if ($unitPrice === null) {
                    throw new InvalidArgumentException("Configured order item unit price column [{$priceColumn}] is missing or null.");
                }
                $return->items()->create([
                    'order_item_id' => $item['order_item_id'], 'quantity' => (int) $item['quantity'],
                    // Never trust a client-supplied return price; snapshot the authoritative order item price.
                    'unit_price' => $unitPrice, 'reason' => $item['reason'] ?? null,
                    'condition' => $item['condition'] ?? null, 'metadata' => $item['metadata'] ?? null,
                ]);
            }
            $this->recordHistory($return, null, 'requested', $data['actor_id'] ?? null, $data['customer_note'] ?? null);
            return $return->load('items');
        });

        Event::dispatch(new ReturnRequested($return));
        return $return;
    }

    public function transition(ReturnRequest $return, string $to, ?int $actorId = null, ?string $note = null): ReturnRequest
    {
        [$updated, $from] = DB::transaction(function () use ($return, $to, $actorId, $note): array {
            $locked = ReturnRequest::query()->lockForUpdate()->findOrFail($return->getKey());
            $from = $locked->status;
            if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
                throw new InvalidReturnTransition("Cannot transition a return from [{$from}] to [{$to}].");
            }
            $locked->status = $to;
            $timestamp = match ($to) {
                'approved' => 'approved_at', 'received' => 'received_at',
                'inspected' => 'inspected_at', 'completed' => 'completed_at', default => null,
            };
            if ($timestamp !== null) {
                $locked->{$timestamp} = now();
            }
            $locked->save();
            $this->recordHistory($locked, $from, $to, $actorId, $note);
            return [$locked->fresh(['items', 'statusHistory']), $from];
        });

        Event::dispatch(new ReturnStatusChanged($updated, $from, $to));
        return $updated;
    }

    private function recordHistory(ReturnRequest $return, ?string $from, string $to, ?int $actorId, ?string $note): void
    {
        ReturnStatusHistory::query()->create([
            'return_request_id' => $return->getKey(), 'from_status' => $from, 'to_status' => $to,
            'actor_id' => $actorId, 'note' => $note, 'created_at' => now(),
        ]);
    }

    private function reference(): string
    {
        return 'RMA-'.strtoupper(bin2hex(random_bytes(5)));
    }
}
