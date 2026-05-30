<?php

declare(strict_types=1);

namespace Modules\Savings\DTOs;

final class CreateSavingsGoalDto
{
    public function __construct(
        public readonly string $userId = '',
        public readonly string $name = '',
        public readonly ?string $nameAr = null,
        public readonly int $targetAmount = 0,
        public readonly ?string $targetDate = null,
        public readonly ?string $category = null,
        public readonly ?string $icon = null,
        public readonly ?string $color = null,
        public readonly bool $autoSweepEnabled = false,
        public readonly ?int $autoSweepAmount = null,
        public readonly ?string $autoSweepFrequency = null,
    ) {}
}
