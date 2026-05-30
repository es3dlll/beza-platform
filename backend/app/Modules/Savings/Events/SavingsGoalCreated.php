<?php

declare(strict_types=1);

namespace Modules\Savings\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class SavingsGoalCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $goalId,
        public readonly string $userId,
        public readonly string $name,
        public readonly int $targetAmount,
    ) {}
}
