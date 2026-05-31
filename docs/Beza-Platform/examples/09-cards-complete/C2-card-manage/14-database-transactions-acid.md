# 14 - ACID + الأقفال + حالات السباق (ACID + Locks + Race Conditions)

## Card Management Race Condition

Without locks, concurrent status updates can cause state corruption:

```
Time  | Request 1 (freeze)           | Request 2 (cancel)
------|------------------------------|------------------------------
T1    | Read card status = active    |
T2    |                               | Read card status = active
T3    | Write status = frozen        |
T4    |                               | Write status = cancelled <- LOST!
```

**Solution**: Optimistic locking with `updated_at` check + atomic UPDATE

## ضمانات ACID (ACID Guarantees)

```
┌─────────────────────────────────────────────────────┐
│    DB::transaction(function) {                       │
│                                                       │
│  Atomicity:    Status change + audit log in one tx    │
│  Consistency:  Valid status transitions only          │
│  Isolation:    FOR UPDATE prevents concurrent writes  │
│  Durability:   InnoDB + binlog persists               │
│                                                       │
│  }                                                    │
└─────────────────────────────────────────────────────┘
```

## آلية القفل (Locking Mechanism)

### Atomic Status Update
```php
// Only updates if status hasn't changed
$affected = Card::where('id', $cardId)
    ->where('status', '!=', 'cancelled')
    ->update(['status' => $newStatus]);
```

### Deadlock Prevention
```php
// Order locks by ID to prevent circular waits
$card = Card::where('id', $cardId)->lockForUpdate()->first();
$wallet = Wallet::where('user_id', $card->user_id)->lockForUpdate()->first();
```

## سيناريوهات الفشل (Failure Scenarios)

| Scenario | What Happens | Recovery |
|----------|-------------|----------|
| Concurrent freeze + cancel | Only one succeeds | Retry with correct state |
| Card already cancelled | UPDATE affects 0 rows | Return error |
| Deadlock | InnoDB picks victim | 3 auto-retries |
