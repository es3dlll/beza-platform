<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Exceptions;

final class AccountNotFoundException extends LedgerException
{
    public function __construct(string $identifier, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: "Account not found: {$identifier}",
            code: self::LEDGER_ACCOUNT_NOT_FOUND,
            previous: $previous,
        );
    }

    public const LEDGER_ACCOUNT_NOT_FOUND = 1001;
}
