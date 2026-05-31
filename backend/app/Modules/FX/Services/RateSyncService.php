<?php

declare(strict_types=1);

namespace App\Modules\Fx\Services;

use App\Modules\Fx\Events\FxRateUpdated;
use App\Modules\Fx\Models\ExchangeRate;
use App\Modules\Fx\Models\RateSource;
use App\Modules\Fx\Exceptions\RateNotFoundException;

final class RateSyncService
{
    public function getBestRate(string $baseCurrency, string $quoteCurrency): ExchangeRate
    {
        $rate = ExchangeRate::where('base_currency', $baseCurrency)
            ->where('quote_currency', $quoteCurrency)
            ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>', now());
            })
            ->orderBy(
                RateSource::select('priority')->whereColumn('rate_sources.id', 'exchange_rates.rate_source_id'),
                'desc'
            )
            ->orderBy('created_at', 'desc')
            ->first();

        if ($rate === null) {
            $rate = ExchangeRate::where('base_currency', $baseCurrency)
                ->where('quote_currency', $quoteCurrency)
                ->where('status', 'manual')
                ->latest()
                ->first();
        }

        if ($rate === null) {
            throw new RateNotFoundException("No rate found for {$baseCurrency}/{$quoteCurrency}");
        }

        return $rate;
    }

    public function updateRate(
        string $sourceId,
        string $baseCurrency,
        string $quoteCurrency,
        int $buyRate,
        int $sellRate,
        int $spreadBps,
        ?int $ttlMinutes = null,
    ): ExchangeRate {
        $rate = ExchangeRate::create([
            'rate_source_id' => $sourceId,
            'base_currency' => $baseCurrency,
            'quote_currency' => $quoteCurrency,
            'buy_rate' => $buyRate,
            'sell_rate' => $sellRate,
            'spread_bps' => $spreadBps,
            'valid_from' => now(),
            'valid_until' => $ttlMinutes !== null ? now()->addMinutes($ttlMinutes) : null,
            'status' => 'active',
        ]);

        event(new FxRateUpdated(
            rateSourceId: $sourceId,
            baseCurrency: $baseCurrency,
            quoteCurrency: $quoteCurrency,
            buyRate: $buyRate,
            sellRate: $sellRate,
            spreadBps: $spreadBps,
        ));

        return $rate;
    }

    public function setManualRate(
        string $baseCurrency,
        string $quoteCurrency,
        int $buyRate,
        int $sellRate,
        int $spreadBps,
    ): ExchangeRate {
        $source = RateSource::firstOrCreate(
            ['type' => 'manual', 'name' => 'Manual Entry', 'name_ar' => 'إدخال يدوي'],
            ['priority' => 0],
        );

        ExchangeRate::where('base_currency', $baseCurrency)
            ->where('quote_currency', $quoteCurrency)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        return ExchangeRate::create([
            'rate_source_id' => $source->id,
            'base_currency' => $baseCurrency,
            'quote_currency' => $quoteCurrency,
            'buy_rate' => $buyRate,
            'sell_rate' => $sellRate,
            'spread_bps' => $spreadBps,
            'valid_from' => now(),
            'status' => 'manual',
        ]);
    }
}
