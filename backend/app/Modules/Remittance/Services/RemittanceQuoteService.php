<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Services;

use App\Modules\Remittance\Enums\CurrencyCode;
use App\Modules\Remittance\Exceptions\InvalidCurrencyPairException;
use App\Modules\Remittance\ValueObjects\ExchangeRate;

final class RemittanceQuoteService
{
    private const FEE_BPS = 50;
    private const MIN_FEE_SYP = 50000;
    private const MAX_FEE_SYP = 5000000;

    public function calculateQuote(
        string $fromCurrency,
        string $toCurrency,
        int $amount,
        ?ExchangeRate $rate = null,
    ): array {
        CurrencyCode::assertValid($fromCurrency);
        CurrencyCode::assertValid($toCurrency);

        if ($fromCurrency === $toCurrency) {
            throw new InvalidCurrencyPairException($fromCurrency, $toCurrency);
        }

        $buyRate = $rate?->buyRate() ?? 25000;
        $spreadBps = $rate?->spreadBps() ?? self::FEE_BPS;

        $destinationAmount = $this->calculateDestination($amount, $buyRate);
        $feeAmount = $this->calculateFee($amount);
        $totalCharge = $amount + $feeAmount;

        return [
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'source_amount' => $amount,
            'destination_amount' => $destinationAmount,
            'buy_rate' => $buyRate,
            'spread_bps' => $spreadBps,
            'fee_amount' => $feeAmount,
            'total_charge' => $totalCharge,
            'quote_expires_in' => 60,
        ];
    }

    private function calculateDestination(int $amount, int $rate): int
    {
        return intdiv($amount * $rate, 10000);
    }

    private function calculateFee(int $amount): int
    {
        $fee = intdiv($amount * self::FEE_BPS, 10000);
        return max(self::MIN_FEE_SYP, min($fee, self::MAX_FEE_SYP));
    }
}
