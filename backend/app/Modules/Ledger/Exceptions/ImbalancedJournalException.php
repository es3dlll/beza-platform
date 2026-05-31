<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Exceptions;

final class ImbalancedJournalException extends LedgerException
{
    public function __construct(int $totalDebits, int $totalCredits, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: "Journal entry is imbalanced: debits {$totalDebits} != credits {$totalCredits}",
            code: self::LEDGER_IMBALANCED_JOURNAL,
            previous: $previous,
        );
    }

    public const LEDGER_IMBALANCED_JOURNAL = 1002;
}
