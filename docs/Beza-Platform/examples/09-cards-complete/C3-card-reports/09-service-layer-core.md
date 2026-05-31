# 09 - طبقة الخدمة (Service Layer)

```php
<?php

namespace App\Services;

use App\Events\ReportGenerated;
use App\Models\Card;
use App\Models\CardTransaction;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CardReportService
{
    public function getTransactions(Card $card, array $filters): LengthAwarePaginator
    {
        return DB::transaction(function () use ($card, $filters) {
            $query = CardTransaction::where('card_id', $card->id);

            if (!empty($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';

            return $query->orderBy($sortBy, $sortOrder)
                ->paginate($filters['per_page'] ?? 15);
        });
    }

    public function getSpendingBreakdown(Card $card, string $period = 'monthly'): array
    {
        return DB::transaction(function () use ($card, $period) {
            $query = CardTransaction::where('card_id', $card->id)
                ->where('status', 'completed');

            if ($period === 'monthly') {
                $breakdown = $query->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"),
                    'category',
                    DB::raw('SUM(amount) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('period', 'category')
                ->orderByDesc('period')
                ->get();
            } else {
                $breakdown = $query->select(
                    'category',
                    DB::raw('SUM(amount) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('category')
                ->orderByDesc('total')
                ->get();
            }

            $grandTotal = $breakdown->sum('total');

            return [
                'period' => $period,
                'grand_total' => $grandTotal,
                'items' => $breakdown->map(fn($item) => [
                    'period' => $item->period ?? null,
                    'category' => $item->category ?? 'uncategorized',
                    'total' => (float) $item->total,
                    'count' => (int) $item->count,
                    'percentage' => $grandTotal > 0
                        ? round($item->total / $grandTotal * 100, 2)
                        : 0,
                ]),
            ];
        });
    }

    public function getFeeSummary(Card $card, Carbon $from, Carbon $to): array
    {
        return DB::transaction(function () use ($card, $from, $to) {
            $fees = CardTransaction::where('card_id', $card->id)
                ->where('type', 'fee')
                ->whereBetween('created_at', [$from, $to])
                ->get();

            return [
                'total_fees' => (float) $fees->sum('amount'),
                'fee_count' => $fees->count(),
                'period_from' => $from->toDateString(),
                'period_to' => $to->toDateString(),
                'breakdown' => $fees->groupBy(fn($f) => $f->created_at->format('Y-m'))
                    ->map(fn($items, $month) => [
                        'month' => $month,
                        'total' => (float) $items->sum('amount'),
                        'count' => $items->count(),
                    ])->values(),
            ];
        });
    }

    public function exportCsv(Card $card, array $filters): string
    {
        $query = CardTransaction::where('card_id', $card->id);

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['ID', 'Date', 'Type', 'Merchant', 'Category', 'Amount', 'Currency', 'Status']);

        $query->chunk(500, function ($transactions) use ($stream) {
            foreach ($transactions as $txn) {
                fputcsv($stream, [
                    $txn->id,
                    $txn->created_at->toDateTimeString(),
                    $txn->type,
                    $txn->merchant,
                    $txn->category,
                    $txn->amount,
                    $txn->currency,
                    $txn->status,
                ]);
            }
        });

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        dispatch(new ReportGenerated($card, 'csv'));

        return $csv;
    }
}
```

## Method Summary

| Method | Returns | Description |
|--------|---------|-------------|
| getTransactions | LengthAwarePaginator | Paginated transaction history with filters |
| getSpendingBreakdown | array | Spending grouped by merchant category |
| getFeeSummary | array | Total fees, count, and monthly breakdown |
| exportCsv | string | CSV content as string |
