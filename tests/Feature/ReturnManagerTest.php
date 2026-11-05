<?php
namespace ItmoSoft\Returns\Tests\Feature;
use Illuminate\Support\Facades\Artisan;
use ItmoSoft\Returns\Exceptions\InvalidReturnTransition;
use ItmoSoft\Returns\Services\ReturnManager;
use ItmoSoft\Returns\Tests\TestCase;

class ReturnManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--database' => 'testing', '--force' => true]);
    }

    public function test_it_creates_return_with_items_and_initial_history(): void
    {
        $return = app(ReturnManager::class)->request(['order_id'=>10,'customer_type'=>'App\\Models\\User','customer_id'=>5,'items'=>[['order_item_id'=>99,'quantity'=>2,'unit_price'=>12.50]]]);
        $this->assertSame('requested', $return->status);
        $this->assertCount(1, $return->items);
        $this->assertSame('requested', $return->statusHistory()->first()->to_status);
    }

    public function test_it_records_valid_status_transitions(): void
    {
        $manager = app(ReturnManager::class);
        $return = $manager->request(['order_id'=>10,'items'=>[['order_item_id'=>99,'quantity'=>1]]]);
        $return = $manager->transition($return, 'approved', 7, 'Approved');
        $this->assertSame('approved', $return->status);
        $this->assertNotNull($return->approved_at);
        $this->assertSame(2, $return->statusHistory()->count());
    }

    public function test_it_rejects_invalid_status_transitions(): void
    {
        $return = app(ReturnManager::class)->request(['order_id'=>10,'items'=>[['order_item_id'=>99,'quantity'=>1]]]);
        $this->expectException(InvalidReturnTransition::class);
        app(ReturnManager::class)->transition($return, 'completed');
    }
}
