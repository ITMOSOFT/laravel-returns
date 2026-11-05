<?php

namespace ItmoSoft\Returns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnStatusHistory extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function getTable(): string
    {
        return config('itmosoft-returns.table_prefix', 'itmosoft_').'return_status_history';
    }
    protected function casts(): array { return ['created_at' => 'datetime', 'metadata' => 'array']; }
    public function returnRequest(): BelongsTo { return $this->belongsTo(ReturnRequest::class, 'return_request_id'); }
}
