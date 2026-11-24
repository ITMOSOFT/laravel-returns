<?php

return [
    'table_prefix' => env('ITMOSOFT_RETURNS_TABLE_PREFIX', 'itmosoft_'),
    'models' => [
        'order' => env('ITMOSOFT_RETURNS_ORDER_MODEL', 'App\\Models\\Order'),
        'return' => \ItmoSoft\Returns\Models\ReturnRequest::class,
        'item' => \ItmoSoft\Returns\Models\ReturnItem::class,
    ],
    // Required integration mapping: set model to your application's order-item Eloquent model.
    'order_items' => [
        'model' => env('ITMOSOFT_RETURNS_ORDER_ITEM_MODEL'),
        'order_id_column' => env('ITMOSOFT_RETURNS_ORDER_ITEM_ORDER_ID_COLUMN', 'order_id'),
        'quantity_column' => env('ITMOSOFT_RETURNS_ORDER_ITEM_QUANTITY_COLUMN', 'quantity'),
        'unit_price_column' => env('ITMOSOFT_RETURNS_ORDER_ITEM_UNIT_PRICE_COLUMN', 'unit_price'),
    ],
    'statuses' => ['requested', 'approved', 'rejected', 'received', 'inspected', 'completed', 'cancelled'],
    'reasons' => ['damaged', 'defective', 'wrong_item', 'not_as_described', 'changed_mind', 'other'],
];
