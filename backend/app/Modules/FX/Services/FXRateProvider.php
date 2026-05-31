<?php

declare(strict_types=1);

namespace App\Modules\FX\Services;

use App\Modules\Core\Enums\Currency;
use App\Modules\FX\Models\ExchangeRate;

final class FXRateProvider
{
    private const SIMULATED_RATES = [
        'SYP_USD' => ['rate' => 12_500, 'spread' => 100],
        'SYP_EUR' => ['rate' => 13_800, 'spread' => 120],
        'SYP_TRY' => ['rate' => 380, 'spread' => 5],
        'USD_SYP' => ['rate' => 1, 'spread' => 0],
        'EUR_SYP' => ['rate' => 1, 'spread' => 0],
        'TRY_SYP' => ['rate' => 1, 'spread' => 0],
    ];

    private const VALIDITY_MINUTES = 5;

    public function getRate(Currency $from, Currency $to, ?string $provider = null): ?ExchangeRate
    {
        $key = "{$from->value}_{$to->value}";

        $rate = ExchangeRate::active()
            ->where('from_currency', $from->value)
            ->where('to_currency', $to->value)
            ->when($provider, fn ($q) => $q->where('provider', $provider))
            ->latest()
            ->first();

        if (!$rate || !$rate->isValid()) {
            $rate = $this->seedRate($from, $to);
        }

        return $rate;
    }

    public function getLatestRates(): array
    {
        $rates = [];
        $pairs = [
            [Currency::SYP, Currency::USD],
            [Currency::SYP, Currency::EUR],
            [Currency::SYP, Currency::TRY],
            [Currency::USD, Currency::SYP],
            [Currency::EUR, Currency::SYP],
            [Currency::TRY, Currency::SYP],
        ];

        foreach ($pairs as [$from, $to]) {
            $rate = $this->getRate($from, $to);
            if ($rate) {
                $rates[] = $rate;
            }
        }

        return $rates;
    }

    public function updateRate(string $fromCurrency, string $toCurrency, int $rateFils, int $bid, int $ask, int $validityMinutes = null): ExchangeRate
    {
        $validityMinutes ??= self::VALIDITY_MINUTES;

        return ExchangeRate::create([
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'rate_fils_per_unit' => $rateFils,
            'bid_fils_per_unit' => $bid,
            'ask_fils_per_unit' => $ask,
            'provider' => 'manual',
            'valid_from' => now(),
            'valid_until' => now()->addMinutes($validityMinutes),
            'is_active' => true,
        ]);
    }

    public function convert(int $amountFils, Currency $from, Currency $to): ?array
    {
        $rate = $this->getRate($from, $to);
        if (!$rate || !$rate->isValid()) {
            return null;
        }

        $convertedAmount = (int) round($amountFils * $rate->rate_fils_per_unit / 1_000_000);

        return [
            'rate' => $rate,
            'from_amount_fils' => $amountFils,
            'to_amount_fils' => $convertedAmount,
            'rate_fils_per_unit' => $rate->rate_fils_per_unit,
        ];
    }

    private function seedRate(Currency $from, Currency $to): ExchangeRate
    {
        $key = "{$from->value}_{$to->value}";

        if (!isset(self::SIMULATED_RATES[$key])) {
            $key = "{$to->value}_{$from->value}";
            if (!isset(self::SIMULATED_RATES[$key])) {
                throw new \RuntimeException("زوج العملات {$from->value}/{$to->value} غير مدعوم");
            }
            $base = self::SIMULATED_RATES[$key];
            $rateFils = (int) round(1_000_000 / ($base['rate'] + $base['spread']));
            $bid = (int) round(1_000_000 / ($base['rate'] + $base['spread'] + 50));
            $ask = (int) round(1_000_000 / ($base['rate'] - 50));
        } else {
            $base = self::SIMULATED_RATES[$key];
            $rateFils = $base['rate'];
            $bid = $base['rate'] - $base['spread'];
            $ask = $base['rate'] + $base['spread'];
        }

        return ExchangeRate::create([
            'from_currency' => $from->value,
            'to_currency' => $to->value,
            'rate_fils_per_unit' => $rateFils,
            'bid_fils_per_unit' => $bid,
            'ask_fils_per_unit' => $ask,
            'provider' => 'simulated',
            'valid_from' => now(),
            'valid_until' => now()->addMinutes(self::VALIDITY_MINUTES),
            'is_active' => true,
        ]);
    }
}
