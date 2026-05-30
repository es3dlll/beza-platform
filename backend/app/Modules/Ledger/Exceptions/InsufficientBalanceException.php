<?php
declare(strict_types=1);

namespace Modules\Ledger\Exceptions;

use Exception;

final class InsufficientBalanceException extends Exception
{
    public function __construct(
        public readonly string $accountId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(
            sprintf(
                'Insufficient balance. Account %s: requested %d, available %d',
                $accountId,
                $requested,
                $available
            )
        );
    }
}
