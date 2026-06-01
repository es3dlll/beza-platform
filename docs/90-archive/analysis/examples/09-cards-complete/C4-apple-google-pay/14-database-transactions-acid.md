# 14 - ACID + الأقفال + حالات السباق (ACID + Locks + Race Conditions)

## Wallet Tokenization Race Condition

Without locks, duplicate token provisioning can occur:

```
Time  | Request 1                    | Request 2
------|------------------------------|------------------------------
T1    | Check token exists = none    |
T2    |                               | Check token exists = none
T3    | INSERT wallet_token          |
T4    |                               | INSERT wallet_token <- DUPLICATE!
```

**Solution**: UNIQUE constraint on (device_id, card_id) + atomic INSERT

## ضمانات ACID (ACID Guarantees)

```
┌─────────────────────────────────────────────────────┐
│    DB::transaction(function) {                       │
│                                                       │
│  Atomicity:    Token creation + activation in one tx  │
│  Consistency:  UNIQUE constraint prevents duplicate   │
│  Isolation:    INSERT with DUPLICATE KEY handling     │
│  Durability:   InnoDB + binlog persists               │
│                                                       │
│  }                                                    │
└─────────────────────────────────────────────────────┘
```

## استراتيجية القفل (Locking Strategy)

### Insert-or-Get Pattern
```php
// Atomic: insert or return existing
$token = WalletToken::firstOrCreate(
    ['device_id' => $deviceId, 'card_id' => $cardId],
    ['status' => 'active']
);
```

### Payment Token Locking
```php
// Lock token row during payment processing
$token = WalletToken::where('token', $paymentToken)
    ->lockForUpdate()
    ->firstOrFail();
```

## سيناريوهات الفشل (Failure Scenarios)

| Scenario | What Happens | Recovery |
|----------|-------------|----------|
| Duplicate device provisioning | firstOrCreate returns existing | OK |
| Token used twice concurrently | Second fails validation | Return error |
| Deadlock during payment | InnoDB auto-rollback | Retry payment |
