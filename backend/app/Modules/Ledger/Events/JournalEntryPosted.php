<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class JournalEntryPosted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $entryId,
        public string $transactionId,
        public array $lines,
        public int $totalDebit,
        public int $totalCredit,
        public string $hash,
    ) {}
}
