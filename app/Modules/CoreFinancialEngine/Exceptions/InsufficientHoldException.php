<?php

namespace Modules\CoreFinancialEngine\Exceptions;

use Exception;

final class InsufficientHoldException extends Exception
{
    public function __construct(string $accountId, int $required, int $available)
    {
        parent::__construct("Insufficient hold on account $accountId: required $required, available $available");
    }
}
