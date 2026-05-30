<?php

declare(strict_types=1);

namespace Modules\Float\Exceptions;

use Exception;

final class InsufficientFloatBalanceException extends Exception
{
    public function __construct(
        public readonly int $balance,
        public readonly int $required,
        public readonly string $floatType,
    ) {
        parent::__construct("Insufficient $floatType float balance: $balance available, $required required");
    }
}
