# 14 - ACID + الأقفال + الـ Race Conditions

## Location Update Race Condition

Without isolation, location updates can interleave incorrectly:

```
Time  | Request 1                    | Request 2
------|------------------------------|------------------------------
T1    | Read last_location = (A)     |
T2    |                               | Read last_location = (A)
T3    | Write location = (B)         |
T4    |                               | Write location = (C) -> OVERWRITE!
```

**Solution**: UPSERT (INSERT ... ON DUPLICATE KEY UPDATE) for location

## ضمانات ACID (ACID Guarantees)

```
┌─────────────────────────────────────────────────────┐
│    DB::transaction(function) {                       │
│                                                       │
│  Atomicity:    Single row UPSERT is atomic            │
│  Consistency:  One location per agent at any time     │
│  Isolation:    UNIQUE constraint prevents duplicates  │
│  Durability:   InnoDB + binlog persists               │
│                                                       │
│  }                                                    │
└─────────────────────────────────────────────────────┘
```

## استراتيجية القفل (Locking Strategy)

### Atomic Location UPSERT
```php
// Insert or update atomically — no race possible
DB::statement("
    INSERT INTO agent_locations (agent_id, latitude, longitude, updated_at)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE latitude = VALUES(latitude),
                            longitude = VALUES(longitude),
                            updated_at = NOW()
", [$agentId, $lat, $lng]);
```

### Batch Update Coordination
```php
// Lock agent row for batch location operations
$agent = Agent::where('id', $agentId)->lockForUpdate()->first();
```

## سيناريوهات الفشل (Failure Scenarios)

| Scenario | What Happens | Recovery |
|----------|-------------|----------|
| Rapid consecutive updates | UPSERT is atomic | Last write wins |
| GPS drift | Same location written | OK |
| Deadlock | InnoDB auto-rollback | Client retries |
