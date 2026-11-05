<?php

namespace ItmoSoft\Returns\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use ItmoSoft\Returns\Events\ReturnRequested;
use ItmoSoft\Returns\Events\ReturnStatusChanged;
use ItmoSoft\Returns\Exceptions\InvalidReturnTransition;
use ItmoSoft\Returns\Models\ReturnRequest;
use ItmoSoft\Returns\Models\ReturnStatusHistory;

class ReturnManager
{
    private const TRANSITIONS = [
        'requested' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['received', 'cancelled'],
        'received' => ['inspected'],
        'inspected' => ['completed'],
        'rejected' => [], 'completed' => [], 'cancelled' => [],
    ];

    public function request(array $data): ReturnRequest
    {
        if (empty($data['items']) || ! is_array($data['items'])) {
            throw ValidationException::withMessages(['items' => 'At least one return item is required.']);
        }
        foreach ($data['items'] as $index => $item) {
            if (empty($item['order_item_id']) || (int) ($item['quantity'] ?? 0) < 1) {
                throw ValidationException::withMessages(["items.$index" => 'Each item requires an order item ID and a positive quantity.']);
            }
        }

        return DB::transaction(function () use ($data) {
            $return = ReturnRequest::query()->create([
                'order_id' => $data['order_id'], 'customer_type' => $data['customer_type'] ?? null,
                'customer_id' => $data['customer_id'] ?? null, 'reference' => $data['reference'] ?? $this->reference(),
                'status' => 'requested', 'reason' => $data['reason'] ?? 'other',
                'customer_note' => $data['customer_note'] ?? null, 'requested_at' => now(), 'metadata' => $data['metadata'] ?? null,
            ]);
            foreach ($data['items'] as $item) {
                $return->items()->create([
                    'order_item_id' => $item['order_item_id'], 'quantity' => (int) $item['quantity'],
                    'unit_price' => $item['unit_price'] ?? 0, 'reason' => $item['reason'] ?? null,
                    'condition' => $item['condition'] ?? null, 'metadata' => $item['metadata'] ?? null,
                ]);
            }
            $this->recordHistory($return, null, 'requested', $data['actor_id'] ?? null, $data['customer_note'] ?? null);
            Event::dispatch(new ReturnRequested($return->load('items')));
            return $return->load('items');
        });
    }

    public function transition(ReturnRequest $return, string $to, ?int $actorId = null, ?string $note = null): ReturnRequest
    {
        return DB::transaction(function () use ($return, $to, $actorId, $note) {
            $locked = ReturnRequest::query()->lockForUpdate()->findOrFail($return->getKey());
            $from = $locked->status;
            if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
                throw new InvalidReturnTransition("Cannot transition a return from [{$from}] to [{$to}].");
            }
            $locked->status = $to;
            $timestamp = match ($to) { 'approved' => 'approved_at', 'received' => 'received_at', 'inspected' => 'inspected_at', 'completed' => 'completed_at', default => null };
            if ($timestamp) $locked->{$timestamp} = now();
            $locked->save();
            $this->recordHistory($locked, $from, $to, $actorId, $note);
            Event::dispatch(new ReturnStatusChanged($locked, $from, $to));
            return $locked->fresh(['items', 'statusHistory']);
        });
    }

    private function recordHistory(ReturnRequest $return, ?string $from, string $to, ?int $actorId, ?string $note): void
    {
        ReturnStatusHistory::query()->create(['return_request_id' => $return->getKey(), 'from_status' => $from, 'to_status' => $to, 'actor_id' => $actorId, 'note' => $note, 'created_at' => now()]);
    }

    private function reference(): string { return 'RMA-'.strtoupper(bin2hex(random_bytes(5))); }
}
