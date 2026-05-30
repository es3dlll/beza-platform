<?php

declare(strict_types=1);

namespace Modules\Savings\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class SavingsProfitDistributed
{
    use Dispatchable;

    public function __construct(
        public readonly string $accountId,
        public readonly string $userId,
        public readonly int $profitAmount,
        public readonly string $period,
    ) {}
}
