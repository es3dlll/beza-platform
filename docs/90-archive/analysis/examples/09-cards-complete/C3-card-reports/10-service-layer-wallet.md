# 10 - طبقة الخدمة - المحفظة (Service Layer - Wallet)

```php
<?php

namespace App\Services;

use App\Models\Card;
use App\Models\CardTransaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletReportService
{
    public function getWalletSpendingBreakdown(Wallet $wallet, string $from, string $to): array
    {
        return DB::transaction(function () use ($wallet, $from, $to) {
            $transactions = CardTransaction::whereHas('card', fn($q) => $q->where('wallet_id', $wallet->id))
                ->where('status', 'completed')
                ->whereBetween('created_at', [$from, $to])
                ->get();

            $total = $transactions->sum('amount');

            return [
                'total_spent' => (float) $total,
                'transaction_count' => $transactions->count(),
                'period_from' => $from,
                'period_to' => $to,
                'by_card' => $transactions->groupBy('card_id')
                    ->map(fn($items, $cardId) => [
                        'card_id' => (int) $cardId,
                        'total' => (float) $items->sum('amount'),
                        'count' => $items->count(),
                    ])->values(),
                'by_category' => $transactions->groupBy('category')
                    ->map(fn($items, $category) => [
                        'category' => $category ?? 'uncategorized',
                        'total' => (float) $items->sum('amount'),
                        'percentage' => $total > 0
                            ? round($items->sum('amount') / $total * 100, 2)
                            : 0,
                    ])->values(),
            ];
        });
    }

    public function getWalletCardMetrics(Wallet $wallet): array
    {
        $cards = Card::where('wallet_id', $wallet->id)->get();

        $totalSpend = CardTransaction::whereHas('card', fn($q) => $q->where('wallet_id', $wallet->id))
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'total_cards' => $cards->count(),
            'active_cards' => $cards->where('status', 'active')->count(),
            'frozen_cards' => $cards->where('status', 'frozen')->count(),
            'total_spend' => (float) $totalSpend,
            'average_per_card' => $cards->count() > 0
                ? round($totalSpend / $cards->count(), 2)
                : 0,
        ];
    }
}
```

## Method Summary

| Method | Returns | Description |
|--------|---------|-------------|
| getWalletSpendingBreakdown | array | Spending by card and category within date range |
| getWalletCardMetrics | array | Total/active/frozen cards + total spend |
