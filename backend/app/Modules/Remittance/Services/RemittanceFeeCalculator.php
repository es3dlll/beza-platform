<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Services;

use App\Modules\Core\ValueObjects\Money;

final class RemittanceFeeCalculator
{
    private const FEE_TIERS = [
        // ['max_amount_fils', 'percentage', 'fixed_fils']
        [1_000_000, 0.02, 0],        // < 1M: 2%
        [5_000_000, 0.015, 0],        // 1M - 5M: 1.5%
        [20_000_000, 0.01, 0],        // 5M - 20M: 1%
        [PHP_INT_MAX, 0.008, 0],       // > 20M: 0.8%
    ];

    private const CURRENCY_PAIR_FEES = [
        'SYP_USD' => 0.001, // 0.1% extra for USD conversions
        'SYP_EUR' => 0.0015, // 0.15% extra for EUR
        'SYP_TRY' => 0.0005, // 0.05% extra for TRY
    ];

    public function calculate(Money $amount, string $fromCurrency, string $toCurrency): array
    {
        $amountFils = $amount->fils();
        $percentage = 0;
        $fixedFee = 0;

        foreach (self::FEE_TIERS as [$max, $pct, $fixed]) {
            if ($amountFils <= $max) {
                $percentage = $pct;
                $fixedFee = $fixed;
                break;
            }
        }

        $percentageFee = (int) round($amountFils * $percentage);
        $pairKey = "{$fromCurrency}_{$toCurrency}";
        $pairSurcharge = isset(self::CURRENCY_PAIR_FEES[$pairKey])
            ? (int) round($amountFils * self::CURRENCY_PAIR_FEES[$pairKey])
            : 0;

        $totalFee = $percentageFee + $fixedFee + $pairSurcharge;

        return [
            'fee_fils' => $totalFee,
            'percentage_fils' => $percentageFee,
            'fixed_fils' => $fixedFee,
            'pair_surcharge_fils' => $pairSurcharge,
            'net_amount_fils' => $amountFils - $totalFee,
            'breakdown' => [
                'percentage_rate' => $percentage,
                'pair_surcharge_rate' => self::CURRENCY_PAIR_FEES[$pairKey] ?? 0,
            ],
        ];
    }

    public function preview(array $feeConfig, int $amountFils, string $fromCurrency, string $toCurrency): array
    {
        $percentageRate = $feeConfig['percentage_rate'] ?? 0.02;
        $pairSurcharge = $feeConfig['pair_surcharge'] ?? (self::CURRENCY_PAIR_FEES["{$fromCurrency}_{$toCurrency}"] ?? 0);

        $fee = (int) round($amountFils * $percentageRate) + (int) round($amountFils * $pairSurcharge);

        return [
            'fee_fils' => $fee,
            'net_amount_fils' => $amountFils - $fee,
        ];
    }
}
