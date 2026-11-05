<?php
namespace ItmoSoft\Returns\Facades;
use Illuminate\Support\Facades\Facade;
class Returns extends Facade { protected static function getFacadeAccessor(): string { return \ItmoSoft\Returns\Services\ReturnManager::class; } }
