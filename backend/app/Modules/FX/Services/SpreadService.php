<?php

declare(strict_types=1);

namespace App\Modules\Fx\Services;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;

final class SpreadService
{
    public function calculateSpreadBps(int $amount, string $kycTier = 't0'): int
    {
        $baseSpread = match ($kycTier) {
            't0' => 300,
            't1' => 200,
            't2' => 100,
            't3' => 50,
            default => 300,
        };

        if ($amount > 10000000) {
            $baseSpread = (int) floor($baseSpread * 0.8);
        }

        if ($amount > 50000000) {
            $baseSpread = (int) floor($baseSpread * 0.6);
        }

        return max($baseSpread, 10);
    }

    public function applySpread(int $rate, int $spreadBps): array
    {
        $spreadFraction = $spreadBps / 10000;
        $halfSpread = $spreadFraction / 2;

        $buyRate = (int) round($rate * (1 - $halfSpread));
        $sellRate = (int) round($rate * (1 + $halfSpread));

        return ['buy_rate' => $buyRate, 'sell_rate' => $sellRate];
    }

    public function inSYP(int $rate, int $amount): int
    {
        return (int) floor(($amount * $rate) / 10000);
    }
}
