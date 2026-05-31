# 14 - ACID + الأقفال + الـ Race Conditions

## Settlement Race Condition

Without locks, double-settlement can occur:

```
Time  | Request 1                    | Request 2
------|------------------------------|------------------------------
T1    | Check settlement pending    |
T2    |                               | Check settlement pending
T3    | UPDATE balance = balance - X |
T4    |                               | UPDATE balance = balance - X <- DOUBLE!
```

**Solution**: Atomic `UPDATE ... WHERE balance >= amount` + FOR UPDATE

## ضمانات ACID (ACID Guarantees)

```
┌─────────────────────────────────────────────────────┐
│    DB::transaction(function) {                       │
│                                                       │
│  Atomicity:    Settlement + balance update in one tx  │
│  Consistency:  Balance never goes negative            │
│  Isolation:    FOR UPDATE prevents double settle      │
│  Durability:   InnoDB + binlog persists               │
│                                                       │
│  }                                                    │
└─────────────────────────────────────────────────────┘
```

## استراتيجية القفل (Locking Strategy)

### Atomic Balance Deduction
```php
// Only succeeds if balance is sufficient
$affected = Wallet::where('id', $walletId)
    ->where('balance', '>=', $amount)
    ->lockForUpdate()
    ->decrement('balance', $amount);
```

### Settlement Locking
```php
// Prevent concurrent settlement of same period
$settlement = Settlement::where('agent_id', $agentId)
    ->where('period', $period)
    ->lockForUpdate()
    ->firstOrFail();
```

## سيناريوهات الفشل (Failure Scenarios)

| Scenario | What Happens | Recovery |
|----------|-------------|----------|
| Double settlement attempted | Atomic decrement fails | Return error |
| Insufficient balance | UPDATE affects 0 rows | Alert agent |
| Deadlock | InnoDB auto-rollback | Retry settlement |
