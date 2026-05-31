<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Exceptions;

final class JournalNotFoundException extends LedgerException
{
    public function __construct(string $id, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: "Journal entry not found: {$id}",
            code: self::LEDGER_JOURNAL_NOT_FOUND,
            previous: $previous,
        );
    }

    public const LEDGER_JOURNAL_NOT_FOUND = 1003;
}
