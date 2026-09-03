<?php

namespace App\Services\Payment;

use App\Models\WebsiteOrder;

class TapPaymentResult
{
    public const CAPTURED = 'captured';
    public const FAILED = 'failed';
    public const PENDING = 'pending';
    public const INVALID = 'invalid';

    public $state;
    public $order;
    public $message;

    public function __construct(string $state, ?WebsiteOrder $order = null, ?string $message = null)
    {
        $this->state = $state;
        $this->order = $order;
        $this->message = $message;
    }
}
