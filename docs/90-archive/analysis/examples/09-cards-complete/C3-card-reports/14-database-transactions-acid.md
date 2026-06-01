# 14 - ACID + الأقفال + حالات السباق (ACID + Locks + Race Conditions)

## Card Reports Race Condition

Without isolation, report queries can get inconsistent snapshots:

```
Time  | Request 1 (generate report) | Request 2 (process tx)
------|------------------------------|------------------------------
T1    | SUM(daily_volume) = 5000     |
T2    |                               | INSERT transaction 1000
T3    | SUM(total_count) counts new  |
T4    | COMMIT report with mismatch  |
```

**Solution**: REPEATABLE READ isolation + snapshot timestamp

## ضمانات ACID (ACID Guarantees)

```
┌─────────────────────────────────────────────────────┐
│    DB::transaction(function) {                       │
│                                                       │
│  Atomicity:    Report generation is read-only         │
│  Consistency:  Snapshot matches a point in time       │
│  Isolation:    REPEATABLE READ prevents phantom rows  │
│  Durability:   Report data persisted in cache         │
│                                                       │
│  }                                                    │
└─────────────────────────────────────────────────────┘
```

## استراتيجية القفل (Locking Strategy)

### Snapshot Isolation
```php
// Reports don't lock — they read committed data
$volume = Transaction::where('created_at', '>=', $since)
    ->where('card_id', $cardId)
    ->sum('amount');
```

### Cache Invalidation
```php
// Invalidate report cache when new transactions occur
Cache::forget("card_report_{$cardId}");
```

## سيناريوهات الفشل (Failure Scenarios)

| Scenario | What Happens | Recovery |
|----------|-------------|----------|
| Stale report data | Minor inconsistency | Regenerate on demand |
| Long-running report | Table locks avoided | Paginated queries |
