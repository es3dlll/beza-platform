<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SettlementCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $batchId,
        public readonly int $transactionCount,
        public readonly int $netAmount,
        public readonly string $settlementAccountId,
    ) {}
}
