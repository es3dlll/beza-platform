<?php

declare(strict_types=1);

namespace Modules\Bills\Exceptions;

use Exception;

final class BillAlreadyPaidException extends Exception
{
    public function __construct(string $paymentId)
    {
        parent::__construct("Bill already paid: {$paymentId}");
    }
}
