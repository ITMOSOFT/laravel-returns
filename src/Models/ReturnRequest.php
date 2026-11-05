<?php

namespace ItmoSoft\Returns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnRequest extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('itmosoft-returns.table_prefix', 'itmosoft_').'return_requests';
    }

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'approved_at' => 'datetime', 'received_at' => 'datetime', 'inspected_at' => 'datetime', 'completed_at' => 'datetime', 'metadata' => 'array'];
    }

    public function order(): BelongsTo
    {
        $model = config('itmosoft-returns.models.order', 'App\\Models\\Order');
        return $this->belongsTo($model, 'order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(config('itmosoft-returns.models.item', ReturnItem::class), 'return_request_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ReturnStatusHistory::class, 'return_request_id')->orderBy('created_at');
    }
}
