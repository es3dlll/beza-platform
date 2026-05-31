# 14 - ACID + الأقفال + الـ Race Conditions

## Agent Registration Race Condition

Without locks, duplicate agent requests can be submitted:

```
Time  | Request 1                    | Request 2
------|------------------------------|------------------------------
T1    | Check pending request = none |
T2    |                               | Check pending request = none
T3    | INSERT agent_requests        |
T4    |                               | INSERT agent_requests <- DUPLICATE!
```

**Solution**: UNIQUE constraint on (user_id, status != rejected) + atomic check

## ضمانات ACID (ACID Guarantees)

```
┌─────────────────────────────────────────────────────┐
│    DB::transaction(function) {                       │
│                                                       │
│  Atomicity:    Request + wallet setup in one tx       │
│  Consistency:  Only one pending request per user      │
│  Isolation:    FOR UPDATE prevents concurrent submit  │
│  Durability:   InnoDB + binlog persists               │
│                                                       │
│  }                                                    │
└─────────────────────────────────────────────────────┘
```

## استراتيجية القفل (Locking Strategy)

### Prevent Duplicate Pending Requests
```php
// Lock user row to serialize registration
$user = User::where('id', $userId)->lockForUpdate()->first();
$existing = AgentRequest::where('user_id', $userId)
    ->whereIn('status', ['pending', 'approved'])
    ->exists();
```

### Atomic Create
```php
DB::transaction(function () use ($user, $data) {
    $request = AgentRequest::create([...]);
    // Create agent wallet
    Wallet::create(['user_id' => $user->id, 'type' => 'agent']);
});
```

## سيناريوهات الفشل (Failure Scenarios)

| Scenario | What Happens | Recovery |
|----------|-------------|----------|
| Duplicate submission | UNIQUE constraint violation | Return existing pending |
| Wallet creation fails | ROLLBACK entire request | User retries |
| Deadlock | InnoDB victim rollback | 3 auto-retries |
