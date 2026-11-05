<?php

namespace ItmoSoft\Returns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('itmosoft-returns.table_prefix', 'itmosoft_').'return_items';
    }
    protected function casts(): array { return ['quantity' => 'integer', 'unit_price' => 'decimal:2', 'metadata' => 'array']; }
    public function returnRequest(): BelongsTo { return $this->belongsTo(ReturnRequest::class, 'return_request_id'); }
}
