<?php

declare(strict_types=1);

namespace Modules\FX\Repositories;

use Illuminate\Support\Collection;
use Modules\FX\Models\FxConversion;

final class FxConversionRepository
{
    public function findById(string $id): ?FxConversion
    {
        return FxConversion::find($id);
    }

    public function findByQuote(string $quoteId): Collection
    {
        return FxConversion::where('quote_id', $quoteId)->get();
    }

    public function findByWallet(string $walletId, int $limit = 20): Collection
    {
        return FxConversion::where(function ($q) use ($walletId) {
            $q->where('from_wallet_id', $walletId)
              ->orWhere('to_wallet_id', $walletId);
        })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function todayTotal(string $walletId, string $direction = 'from'): int
    {
        $col = $direction === 'from' ? 'from_amount' : 'to_amount';
        return (int) FxConversion::where($direction . '_wallet_id', $walletId)
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum($col);
    }

    public function save(FxConversion $conversion): FxConversion
    {
        $conversion->save();
        return $conversion;
    }
}
