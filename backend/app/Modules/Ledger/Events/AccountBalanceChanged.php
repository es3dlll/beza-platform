<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class AccountBalanceChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $accountId,
        public int $previousBalance,
        public int $newBalance,
        public int $delta,
        public string $direction,
        public string $transactionId,
    ) {}
}
