<?php

namespace ItmoSoft\Returns\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use ItmoSoft\Returns\Exceptions\InvalidReturnTransition;
use ItmoSoft\Returns\Services\ReturnManager;
use ItmoSoft\Returns\Tests\ReturnTestOrderItem;
use ItmoSoft\Returns\Tests\TestCase;

class ReturnManagerTest extends TestCase
{
    private function orderItem(int $orderId = 10, int $quantity = 3, string $price = '12.50'): ReturnTestOrderItem
    {
        return ReturnTestOrderItem::query()->create(['order_id' => $orderId, 'quantity' => $quantity, 'unit_price' => $price]);
    }

    public function test_it_creates_return_with_items_and_initial_history_using_authoritative_price(): void
    {
        $orderItem = $this->orderItem();
        $return = app(ReturnManager::class)->request(['order_id' => 10, 'customer_type' => 'App\\Models\\User', 'customer_id' => 5,
            'items' => [['order_item_id' => $orderItem->id, 'quantity' => 2, 'unit_price' => 0.01]]]);
        $this->assertSame('requested', $return->status);
        $this->assertCount(1, $return->items);
        $this->assertSame('12.50', $return->items[0]->unit_price);
        $this->assertSame('requested', $return->statusHistory()->first()->to_status);
    }

    public function test_it_rejects_an_order_item_that_belongs_to_another_order(): void
    {
        $item = $this->orderItem(11);
        $this->expectException(ValidationException::class);
        app(ReturnManager::class)->request(['order_id' => 10, 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]]);
    }

    public function test_it_rejects_return_quantity_above_purchased_quantity(): void
    {
        $item = $this->orderItem(10, 2);
        $this->expectException(ValidationException::class);
        app(ReturnManager::class)->request(['order_id' => 10, 'items' => [['order_item_id' => $item->id, 'quantity' => 3]]]);
    }

    public function test_it_counts_prior_open_returns_when_validating_remaining_quantity(): void
    {
        $item = $this->orderItem(10, 3);
        $manager = app(ReturnManager::class);
        $manager->request(['order_id' => 10, 'items' => [['order_item_id' => $item->id, 'quantity' => 2]]]);
        $this->expectException(ValidationException::class);
        $manager->request(['order_id' => 10, 'items' => [['order_item_id' => $item->id, 'quantity' => 2]]]);
    }

    public function test_it_records_valid_status_transitions(): void
    {
        $item = $this->orderItem();
        $manager = app(ReturnManager::class);
        $return = $manager->request(['order_id' => 10, 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]]);
        $return = $manager->transition($return, 'approved', 7, 'Approved');
        $this->assertSame('approved', $return->status);
        $this->assertNotNull($return->approved_at);
        $this->assertSame(2, $return->statusHistory()->count());
    }

    public function test_it_rejects_invalid_status_transitions(): void
    {
        $item = $this->orderItem();
        $return = app(ReturnManager::class)->request(['order_id' => 10, 'items' => [['order_item_id' => $item->id, 'quantity' => 1]]]);
        $this->expectException(InvalidReturnTransition::class);
        app(ReturnManager::class)->transition($return, 'completed');
    }
}
