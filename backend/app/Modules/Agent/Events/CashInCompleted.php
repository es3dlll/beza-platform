<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class CashInCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $agentTransactionId,
        public readonly string $agentId,
        public readonly string $customerWalletId,
        public readonly int $amount,
        public readonly int $commissionAmount,
        public readonly string $transactionId,
    ) {}
}
