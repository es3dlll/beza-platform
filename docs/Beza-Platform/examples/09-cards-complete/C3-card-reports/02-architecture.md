# 02 - Ø§Ù„Ø¨Ù†ÙŠØ© Ø§Ù„Ù…Ø¹Ù…Ø§Ø±ÙŠØ© (Architecture) - ØªÙ‚Ø§Ø±ÙŠØ± Ø§Ù„Ø¨Ø·Ø§Ù‚Ø©

## Layer Stack

```
Flutter/React SPA --> API Gateway --> Controller --> CardReportService --> Database
                          |                    |             |
                     Redis Cache         Validation     Aggregation
```

## Request Flow

1. **Client** sends `GET /api/v1/cards/{id}/transactions` with query filters
2. **API Gateway** applies auth (JWT) + rate limiting (throttle: 60,1)
3. **CardReportController** validates request via `CardReportRequest` FormRequest
4. **CardReportService** checks Redis cache for cached report data
5. If cache miss, service executes aggregation pipeline:
   - Queries `card_transactions` with filters
   - Aggregates by category, merchant, date
   - Computes totals, averages, counts
   - Stores result in Redis with TTL (5 minutes)
6. Returns paginated JSON response

## Aggregation Pipeline

```php
<?php

namespace App\Services;

use App\Models\CardTransaction;
use Illuminate\Support\Facades\DB;

class CardReportService
{
    public function getReports(int $cardId, array $filters): array
    {
        $cacheKey = "card_report:{$cardId}:" . md5(json_encode($filters));

        return Cache::remember($cacheKey, 300, function () use ($cardId, $filters) {
            return DB::transaction(function () use ($cardId, $filters) {
                $query = CardTransaction::where('card_id', $cardId);

                if (!empty($filters['date_from'])) {
                    $query->where('created_at', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $query->where('created_at', '<=', $filters['date_to']);
                }
                if (!empty($filters['category'])) {
                    $query->where('category', $filters['category']);
                }

                $totalAmount = (clone $query)->sum('amount');
                $totalCount = (clone $query)->count();

                $byCategory = (clone $query)
                    ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                    ->groupBy('category')
                    ->get();

                $transactions = $query->orderBy('created_at', 'desc')
                    ->paginate($filters['per_page'] ?? 15);

                return [
                    'summary' => [
                        'total_amount' => $totalAmount,
                        'total_transactions' => $totalCount,
                        'average_amount' => $totalCount > 0 ? round($totalAmount / $totalCount, 2) : 0,
                    ],
                    'by_category' => $byCategory,
                    'transactions' => $transactions,
                ];
            });
        });
    }
}
```

## Caching Layer

| Aspect | Configuration |
|--------|--------------|
| Driver | Redis |
| Key pattern | `card_report:{card_id}:{md5(filters)}` |
| TTL | 300 seconds (5 minutes) |
| Cache invalidation | On new transaction for that card |
| Fallback | Database query if Redis unavailable |

## Related Files

- Controller: `CardReportController`
- Service: `CardReportService`
- Request: `CardReportRequest`
- Model: `Card`, `CardTransaction`
- Migration: `create_card_transactions_table`
