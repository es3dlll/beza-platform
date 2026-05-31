<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TransactionReversed
{
    use Dispatchable;

    public function __construct(
        public readonly string $originalTransactionId,
        public readonly string $reversalTransactionId,
        public readonly string $reason,
        public readonly string $journalEntryId,
    ) {}
}
