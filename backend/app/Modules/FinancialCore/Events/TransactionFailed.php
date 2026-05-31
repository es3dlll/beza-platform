<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TransactionFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $transactionId,
        public readonly string $reason,
    ) {}
}
