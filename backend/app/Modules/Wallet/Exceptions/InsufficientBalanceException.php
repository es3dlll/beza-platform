<?php

declare(strict_types=1);

namespace Modules\Wallet\Exceptions;

use Exception;

final class InsufficientBalanceException extends Exception
{
    public function __construct(
        public readonly int $required,
        public readonly int $available,
        public readonly string $currency = 'SYP',
    ) {
        parent::__construct("Insufficient balance: required $required, available $available");
    }
}
