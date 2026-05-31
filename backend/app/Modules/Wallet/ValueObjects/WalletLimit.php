<?php

declare(strict_types=1);

namespace App\Modules\Wallet\ValueObjects;

use App\Modules\Wallet\Exceptions\InvalidLimitException;

final readonly class WalletLimit
{
    private const SYP_MINOR_UNITS = 100; // 1 SYP = 100 فلس

    public function __construct(
        private int $dailyMax,
        private int $monthlyMax,
        private int $singleMax,
        private int $dailyUsed = 0,
        private int $monthlyUsed = 0,
    ) {
        if ($dailyMax < 0 || $monthlyMax < 0 || $singleMax < 0) {
            throw new InvalidLimitException('Limits cannot be negative');
        }
        if ($dailyUsed < 0 || $monthlyUsed < 0) {
            throw new InvalidLimitException('Used amounts cannot be negative');
        }
        if ($singleMax > $dailyMax) {
            throw new InvalidLimitException('Single transaction limit cannot exceed daily limit');
        }
        if ($dailyMax > $monthlyMax) {
            throw new InvalidLimitException('Daily limit cannot exceed monthly limit');
        }
    }

    public function dailyMaxSyp(): float
    {
        return $this->dailyMax / self::SYP_MINOR_UNITS;
    }

    public function monthlyMaxSyp(): float
    {
        return $this->monthlyMax / self::SYP_MINOR_UNITS;
    }

    public function singleMaxSyp(): float
    {
        return $this->singleMax / self::SYP_MINOR_UNITS;
    }

    public function dailyRemaining(): int
    {
        return max(0, $this->dailyMax - $this->dailyUsed);
    }

    public function monthlyRemaining(): int
    {
        return max(0, $this->monthlyMax - $this->monthlyUsed);
    }

    public function canProcess(int $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }
        if ($amount > $this->singleMax) {
            return false;
        }
        if ($amount > $this->dailyRemaining()) {
            return false;
        }
        if ($amount > $this->monthlyRemaining()) {
            return false;
        }
        return true;
    }

    public function withUsage(int $dailyUsed, int $monthlyUsed): self
    {
        return new self(
            dailyMax: $this->dailyMax,
            monthlyMax: $this->monthlyMax,
            singleMax: $this->singleMax,
            dailyUsed: $dailyUsed,
            monthlyUsed: $monthlyUsed,
        );
    }

    public function toArray(): array
    {
        return [
            'daily_max_syp' => $this->dailyMaxSyp(),
            'monthly_max_syp' => $this->monthlyMaxSyp(),
            'single_max_syp' => $this->singleMaxSyp(),
            'daily_remaining' => $this->dailyRemaining(),
            'monthly_remaining' => $this->monthlyRemaining(),
            'daily_used' => $this->dailyUsed,
            'monthly_used' => $this->monthlyUsed,
        ];
    }
}
