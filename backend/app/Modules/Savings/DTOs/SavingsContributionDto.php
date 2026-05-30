<?php

declare(strict_types=1);

namespace Modules\Savings\DTOs;

class SavingsContributionDto
{
    public function __construct(
        public readonly string $savingsGoalId = '',
        public readonly string $userId = '',
        public readonly int $amount = 0,
        public readonly ?string $description = null,
    ) {}
}
