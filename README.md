# ITMOSOFT Laravel Returns

Return merchandise authorization (RMA) workflows for Laravel commerce applications.

## Features
- Return requests linked to order IDs, with optional polymorphic customer identity.
- Multiple return items per request with order-item references, quantities, unit-price snapshots, reasons and condition.
- Status lifecycle: requested, approved, rejected, received, inspected, completed and cancelled.
- Validated status transitions, transaction/row locking and status history.
- `ReturnRequested` and `ReturnStatusChanged` events.
- Configurable table prefix and order model.

## Requirements
- PHP 8.3+
- Laravel 12 or 13

## Installation
```bash
composer require itmosoft/laravel-returns
php artisan vendor:publish --tag=itmosoft-returns-config
php artisan migrate
```
The package loads its migration automatically. Publish the config only if you need to customize it. The migration creates the return requests, return items and return status history tables using the configured table prefix.

## Usage
```php
use ItmoSoft\Returns\Services\ReturnManager;

$return = app(ReturnManager::class)->request([
    'order_id' => $order->id,
    'customer_type' => $user::class,
    'customer_id' => $user->id,
    'reason' => 'damaged',
    'customer_note' => 'The item arrived damaged.',
    'items' => [
        ['order_item_id' => $orderItem->id, 'quantity' => 1, 'unit_price' => '49.99'],
    ],
]);

$return = app(ReturnManager::class)->transition($return, 'approved', auth()->id(), 'Eligible for return');
$return = app(ReturnManager::class)->transition($return, 'received', auth()->id());
$return = app(ReturnManager::class)->transition($return, 'inspected', auth()->id(), 'Packaging intact');
$return = app(ReturnManager::class)->transition($return, 'completed', auth()->id());
```

Allowed transitions:
- requested → approved, rejected, cancelled
- approved → received, cancelled
- received → inspected
- inspected → completed

Terminal states cannot transition further. Invalid transitions throw `InvalidReturnTransition`.

## Configuration
`config/itmosoft-returns.php` supports `table_prefix`, `models.order`, `models.return`, `models.item`, `statuses`, and `reasons`. Environment variables: `ITMOSOFT_RETURNS_TABLE_PREFIX`, `ITMOSOFT_RETURNS_ORDER_MODEL`.

## Integration boundaries
This package does not issue refunds, update inventory, create shipping labels, validate original order quantities, or enforce a return window. Applications should authorize return eligibility and quantity limits before calling `request()`. Connect refund and inventory packages through listeners after a return reaches the appropriate state.

## Testing
```bash
composer install
composer test
```

## License
MIT. See [LICENSE](LICENSE).
