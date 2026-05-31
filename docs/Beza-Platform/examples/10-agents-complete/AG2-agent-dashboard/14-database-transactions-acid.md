# 14 - ACID + الأقفال + الـ Race Conditions

## Dashboard Stats Race Condition

Without isolation, concurrent transactions can produce inconsistent aggregates:

```
Time  | Request 1 (read stats)      | Request 2 (new tx)
------|------------------------------|------------------------------
T1    | COUNT(today_tx) = 50         |
T2    |                               | INSERT transaction
T3    | SUM(volume) misses new tx    |
T4    | Dashboard shows stale data   |
```

**Solution**: Cached counters invalidated on write + eventual consistency

## ضمانات ACID (ACID Guarantees)

```
┌─────────────────────────────────────────────────────┐
│    DB::transaction(function) {                       │
│                                                       │
│  Atomicity:    All dashboard queries in one snapshot  │
│  Consistency:  Aggregates use committed data only     │
│  Isolation:    READ COMMITTED for liveliness          │
│  Durability:   Cache with TTL prevents stale reads    │
│                                                       │
│  }                                                    │
└─────────────────────────────────────────────────────┘
```

## استراتيجية القفل (Locking Strategy)

### Cache-First Pattern
```php
// Dashboard reads from cache, not live DB
$stats = Cache::remember("agent_dashboard_{$agentId}", 60, function () {
    return AgentStat::where('agent_id', $agentId)->first();
});
```

### Periodic Refresh
```php
// Recalculate stats every 5 minutes via scheduled job
DB::transaction(function () use ($agentId) {
    $stats = AgentStat::where('agent_id', $agentId)->lockForUpdate()->first();
    $stats->recalculate();
});
```

## سيناريوهات الفشل (Failure Scenarios)

| Scenario | What Happens | Recovery |
|----------|-------------|----------|
| Stale cache | Slight delay in updates | TTL auto-refresh |
| Concurrent recalc | Lock serializes access | OK |
| DB down | Cache serves stale data | Fallback to stale |
