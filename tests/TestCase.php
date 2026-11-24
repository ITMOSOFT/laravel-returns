<?php

namespace ItmoSoft\Returns\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ItmoSoft\Returns\ReturnsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class ReturnTestOrderItem extends Model
{
    protected $table = 'order_items';
    protected $guarded = [];
    public $timestamps = false;
}

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ReturnsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]);
        $app['config']->set('itmosoft-returns.order_items.model', ReturnTestOrderItem::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
        });
        $this->artisan('migrate', ['--database' => 'testing', '--force' => true]);
    }
}
