<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TransactionPosted
{
    use Dispatchable;

    public function __construct(
        public readonly string $transactionId,
        public readonly int $amount,
        public readonly string $fromWalletId,
        public readonly string $toWalletId,
        public readonly string $journalEntryId,
    ) {}
}
