<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = (string) config('itmosoft-returns.table_prefix', 'itmosoft_');
        $requests = $prefix.'return_requests';
        $items = $prefix.'return_items';
        $history = $prefix.'return_status_history';

        Schema::create($requests, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('customer_type')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('reference', 64)->unique();
            $table->string('status', 32)->index();
            $table->string('reason', 64)->default('other');
            $table->text('customer_note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['customer_type', 'customer_id'], 'itmosoft_ret_customer_idx');
        });

        Schema::create($items, function (Blueprint $table) use ($requests): void {
            $table->id();
            $table->unsignedBigInteger('return_request_id');
            $table->unsignedBigInteger('order_item_id')->index();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->string('reason', 64)->nullable();
            $table->string('condition', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('return_request_id', 'itmosoft_ret_items_req_fk')
                ->references('id')->on($requests)->cascadeOnDelete();
        });

        Schema::create($history, function (Blueprint $table) use ($requests): void {
            $table->id();
            $table->unsignedBigInteger('return_request_id');
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->foreign('return_request_id', 'itmosoft_ret_history_req_fk')
                ->references('id')->on($requests)->cascadeOnDelete();
            $table->index(['return_request_id', 'created_at'], 'itmosoft_ret_history_created_idx');
        });
    }

    public function down(): void
    {
        $prefix = (string) config('itmosoft-returns.table_prefix', 'itmosoft_');

        Schema::dropIfExists($prefix.'return_status_history');
        Schema::dropIfExists($prefix.'return_items');
        Schema::dropIfExists($prefix.'return_requests');
    }
};
