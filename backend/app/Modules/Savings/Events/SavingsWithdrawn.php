<?php

declare(strict_types=1);

namespace Modules\Savings\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SavingsWithdrawn
{
    use Dispatchable;

    public function __construct(
        public readonly string $goalId,
        public readonly string $userId,
        public readonly int $amount,
        public readonly int $penaltyApplied,
    ) {}
}
