<?php
namespace ItmoSoft\Returns\Events;
use ItmoSoft\Returns\Models\ReturnRequest;
class ReturnStatusChanged { public function __construct(public readonly ReturnRequest $returnRequest, public readonly string $from, public readonly string $to) {} }
