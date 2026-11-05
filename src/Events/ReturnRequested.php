<?php
namespace ItmoSoft\Returns\Events;
use ItmoSoft\Returns\Models\ReturnRequest;
class ReturnRequested { public function __construct(public readonly ReturnRequest $returnRequest) {} }
