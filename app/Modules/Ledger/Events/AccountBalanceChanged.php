<?php
declare(strict_types=1);

namespace Modules\Ledger\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AccountBalanceChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $accountId,
        public readonly string $accountNumber,
        public readonly int $previousBalance,
        public readonly int $newBalance,
        public readonly int $change,
        public readonly string $direction,
        public readonly string $currency,
        public readonly string $journalEntryId,
    ) {}
}
