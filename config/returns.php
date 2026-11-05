<?php

return [
    'table_prefix' => env('ITMOSOFT_RETURNS_TABLE_PREFIX', 'itmosoft_'),
    'models' => [
        'order' => env('ITMOSOFT_RETURNS_ORDER_MODEL', 'App\\Models\\Order'),
        'return' => \ItmoSoft\Returns\Models\ReturnRequest::class,
        'item' => \ItmoSoft\Returns\Models\ReturnItem::class,
    ],
    'statuses' => ['requested', 'approved', 'rejected', 'received', 'inspected', 'completed', 'cancelled'],
    'reasons' => ['damaged', 'defective', 'wrong_item', 'not_as_described', 'changed_mind', 'other'],
];
