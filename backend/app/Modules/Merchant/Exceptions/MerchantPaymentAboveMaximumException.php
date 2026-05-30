<?php

declare(strict_types=1);

namespace Modules\Merchant\Exceptions;

use Exception;

final class MerchantPaymentAboveMaximumException extends Exception
{
    public function __construct(int $amount, int $max)
    {
        parent::__construct("Payment amount {$amount} exceeds merchant maximum {$max}");
    }
}
