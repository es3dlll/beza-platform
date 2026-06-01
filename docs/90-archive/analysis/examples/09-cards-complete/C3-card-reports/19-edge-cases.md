# 19 - حالات الحافة (Edge Cases)

## 1. Large Datasets (Millions of Transactions)

**Problem**: Queries on large datasets timeout or consume excessive memory.

**Solution**:
- Use chunked queries with `cursor()` or `chunk()` for processing
- Implement date range limits (max 90 days per request)
- Use materialized views for pre-aggregated monthly data
- Apply database-level pagination, never load all rows

```php
// Chunked processing for large exports
CardTransaction::where('card_id', $cardId)
    ->whereBetween('created_at', [$from, $to])
    ->orderBy('id')
    ->chunk(1000, function ($transactions) use (&$csv) {
        foreach ($transactions as $txn) {
            $csv[] = $txn->toArray();
        }
    });
```

## 2. Timezone Issues

**Problem**: Transactions created in different timezones produce incorrect daily/monthly aggregates.

**Solution**:
- Store all timestamps in UTC in the database
- Convert to user's timezone at the application layer
- Use `$user->timezone` preference for report grouping

```php
$userTimezone = $request->user()->timezone ?? 'UTC';
$query->whereDate('created_at', '>=', Carbon::parse($dateFrom, $userTimezone)->utc());
```

## 3. Deleted (Soft-Deleted) Cards

**Problem**: Card is soft-deleted but user still needs historical reports.

**Solution**:
- Use `withTrashed()` for historical data queries
- Show a label "(محذوفة)" next to deleted card names
- Prevent new transactions on deleted cards but allow report access

```php
$card = Card::withTrashed()->where('user_id', $userId)->findOrFail($cardId);
```

## 4. Partial Months

**Problem**: Current month is incomplete, causing misleading comparisons.

**Solution**:
- Project partial month totals using daily averages
- Show a clear indicator for partial data periods
- Annualize partial month data for comparison

```php
$daysSoFar = now()->day;
$projectedTotal = $actualTotal / $daysSoFar * Carbon::now()->daysInMonth;
```

## 5. Currency Fluctuations

**Problem**: Multi-currency reports need conversion to base currency.

**Solution**:
- Store exchange rate at transaction time
- Convert all amounts to base currency (SAR) at report time
- Cache exchange rates for consistency within a report session

## 6. Concurrent Report Generation

**Problem**: Multiple report requests for the same data cause redundant processing.

**Solution**:
- Use Redis locking for expensive aggregations
- Cache results with TTL
- Implement request deduplication

## Edge Case Summary

| # | Case | Solution | Level |
|---|------|----------|-------|
| 1 | Large datasets | Chunking + date limits | Query |
| 2 | Timezone issues | UTC storage + user TZ conversion | Application |
| 3 | Deleted cards | withTrashed() | Query |
| 4 | Partial months | Daily average projection | Application |
| 5 | Multi-currency | Stored exchange rates | Calculation |
| 6 | Concurrent requests | Redis cache + locks | Cache |
