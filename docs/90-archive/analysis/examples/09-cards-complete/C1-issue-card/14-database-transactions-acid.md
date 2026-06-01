# 14 - ACID + الأقفال + حالات السباق (ACID + Locks + Race Conditions)

## Card Issuing Race Condition

Without locks, concurrent issue requests can create duplicate cards:

```
Time  | Request 1                    | Request 2
------|------------------------------|------------------------------
T1    | Check existing card = none   |
T2    |                               | Check existing card = none
T3    | INSERT cards (status=active) |
T4    |                               | INSERT cards (status=active) <- DUPLICATE!
```

**Solution**: Unique constraint on (user_id, card_type) + SELECT FOR UPDATE

## ضمانات ACID (ACID Guarantees)

```
┌─────────────────────────────────────────────────────┐
│    DB::transaction(function) {                       │
│                                                       │
│  Atomicity:    All or nothing (card + wallet)         │
│  Consistency:  UNIQUE constraint prevents dupes       │
│  Isolation:    FOR UPDATE prevents concurrent issues  │
│  Durability:   InnoDB + binlog persists after commit  │
│                                                       │
│  }                                                    │
└─────────────────────────────────────────────────────┘
```

## آلية القفل (Locking Mechanism)

### Pessimistic Lock (FOR UPDATE)
```php
// Prevents duplicate card issuance per user
Card::where('user_id', $userId)
    ->where('type', $cardType)
    ->lockForUpdate()
    ->first();
```

### Deadlock Prevention
```php
// Always lock in consistent order (user_id first)
DB::transaction(function () use ($user, $data) {
    $wallet = Wallet::where('user_id', $user->id)
        ->lockForUpdate()
        ->first();
    $card = Card::create([...]);
});
```

## سيناريوهات الفشل (Failure Scenarios)

| Scenario | What Happens | Recovery |
|----------|-------------|----------|
| Duplicate card request | UNIQUE constraint violation | Return existing card |
| Deadlock | InnoDB picks victim | 3 auto-retries |
| Card creation fails mid-way | ROLLBACK | User retries |
