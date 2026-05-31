<?php

declare(strict_types=1);

namespace App\Modules\Wallet\ValueObjects;

use App\Modules\Wallet\Exceptions\InvalidBalanceException;

final readonly class BalanceSnapshot
{
    private const SYP_MINOR_UNITS = 100;

    public function __construct(
        private int $settledBalance,
        private int $pendingBalance,
        private string $currency = 'SYP',
    ) {
        if ($settledBalance < 0) {
            throw new InvalidBalanceException('Settled balance cannot be negative');
        }
        if ($pendingBalance < 0) {
            throw new InvalidBalanceException('Pending balance cannot be negative');
        }
    }

    public function availableBalance(): int
    {
        return $this->settledBalance - $this->pendingBalance;
    }

    public function availableSyp(): float
    {
        return $this->availableBalance() / self::SYP_MINOR_UNITS;
    }

    public function settledSyp(): float
    {
        return $this->settledBalance / self::SYP_MINOR_UNITS;
    }

    public function hasSufficientFunds(int $amount): bool
    {
        return $amount > 0 && $this->availableBalance() >= $amount;
    }

    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'settled' => $this->settledSyp(),
            'pending' => $this->pendingBalance / self::SYP_MINOR_UNITS,
            'available' => $this->availableSyp(),
            'has_sufficient_funds' => $this->availableBalance() > 0,
        ];
    }
}
