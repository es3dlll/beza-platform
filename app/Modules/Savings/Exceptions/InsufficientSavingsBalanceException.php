<?php

declare(strict_types=1);

namespace Modules\Savings\Exceptions;

use Exception;

class InsufficientSavingsBalanceException extends Exception
{
    public function __construct(int $required, int $available)
    {
        parent::__construct("Insufficient savings balance: required {$required}, available {$available}");
    }
}
