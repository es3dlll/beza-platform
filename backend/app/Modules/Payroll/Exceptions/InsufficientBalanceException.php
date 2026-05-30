<?php

declare(strict_types=1);

namespace Modules\Payroll\Exceptions;

use Exception;

final class InsufficientBalanceException extends Exception
{
    public function __construct(int $required, int $available)
    {
        parent::__construct("Insufficient balance: required {$required}, available {$available}");
    }
}
