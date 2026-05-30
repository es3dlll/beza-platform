<?php

declare(strict_types=1);

namespace Modules\Bills\Exceptions;

use Exception;

final class BillPaymentFailedException extends Exception
{
    public function __construct(string $reason = 'Biller rejected the payment')
    {
        parent::__construct("Bill payment failed: {$reason}");
    }
}
