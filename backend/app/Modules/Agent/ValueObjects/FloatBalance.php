<?php

declare(strict_types=1);

namespace App\Modules\Agent\ValueObjects;

use App\Modules\Agent\Exceptions\InsufficientFloatException;

final readonly class FloatBalance
{
    public function __construct(
        private int $available,
        private int $pending,
        private int $minimumRequired,
        private int $dailyLimit,
        private int $dailyUsed,
    ) {}

    public function available(): int { return $this->available; }
    public function pending(): int { return $this->pending; }
    public function minimumRequired(): int { return $this->minimumRequired; }
    public function dailyLimit(): int { return $this->dailyLimit; }
    public function dailyUsed(): int { return $this->dailyUsed; }
    public function total(): int { return $this->available + $this->pending; }

    public function canDeduct(int $amount): bool
    {
        return $this->available >= $amount;
    }

    public function assertSufficient(int $amount): void
    {
        if (!$this->canDeduct($amount)) {
            throw new InsufficientFloatException(
                "Insufficient float balance: available {$this->available}, requested {$amount}"
            );
        }
    }

    public function isBelowMinimum(): bool
    {
        return $this->available < $this->minimumRequired;
    }

    public function withinDailyLimit(int $amount): bool
    {
        return ($this->dailyUsed + $amount) <= $this->dailyLimit;
    }

    public function toArray(): array
    {
        return [
            'available' => $this->available,
            'pending' => $this->pending,
            'minimum_required' => $this->minimumRequired,
            'daily_limit' => $this->dailyLimit,
            'daily_used' => $this->dailyUsed,
            'below_minimum' => $this->isBelowMinimum(),
        ];
    }
}
