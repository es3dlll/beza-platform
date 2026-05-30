<?php

declare(strict_types=1);

namespace Modules\FX\Repositories;

use Illuminate\Support\Collection;
use Modules\FX\Models\FxRate;

final class FxRateRepository
{
    public function findById(string $id): ?FxRate
    {
        return FxRate::find($id);
    }

    public function findActive(string $baseCurrency, string $quoteCurrency, string $rateType = 'cbs_official'): ?FxRate
    {
        return FxRate::where('base_currency', $baseCurrency)
            ->where('quote_currency', $quoteCurrency)
            ->where('rate_type', $rateType)
            ->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>', now());
            })
            ->orderBy('published_at', 'desc')
            ->first();
    }

    public function findLatestForPair(string $baseCurrency, string $quoteCurrency): Collection
    {
        return FxRate::where('base_currency', $baseCurrency)
            ->where('quote_currency', $quoteCurrency)
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function findAllActive(): Collection
    {
        return FxRate::where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>', now());
            })
            ->orderBy('published_at', 'desc')
            ->get();
    }

    public function save(FxRate $rate): FxRate
    {
        $rate->save();
        return $rate;
    }
}
