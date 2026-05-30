# Remittance Infrastructure

## Deployment Architecture
```
┌──────────────────────────────────────────────────────────────────┐
│                      Kubernetes Cluster                          │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │
│  │  Laravel API  │  │  Queue       │  │  Scheduler   │           │
│  │  Replicas: 3  │  │  Workers: 8  │  │  Cron: 1     │           │
│  │  CPU: 2, RAM:4│  │  CPU: 1,RAM:2│  │  CPU: 0.5    │           │
│  └──────────────┘  └──────────────┘  └──────────────┘           │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │
│  │  Redis Cache  │  │  MySQL       │  │  RabbitMQ    │           │
│  │  Replicas: 3  │  │  Primary+3 RO│  │  Cluster 3   │           │
│  │  (1 for FX)   │  │  (partitioned│  │              │           │
│  └──────────────┘  │  by corridor)│  └──────────────┘           │
│                    └──────────────┘                              │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │                    FX Engine Service                      │    │
│  │  Replicas: 2  │  Memory: 4GB  │  Rate updates: 1s       │    │
│  └──────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │              Compliance Screening Service                 │    │
│  │  Replicas: 2  │  Memory: 8GB  │  Sanctions DB cache      │    │
│  └──────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │              Recurring Execution Service                  │    │
│  │  Replicas: 1  │  Trigger: Cron (every 5 min)             │    │
│  └──────────────────────────────────────────────────────────┘    │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

## Scaling Strategy
```
Remittance API:
  - HPA: CPU > 70% OR memory > 75% → scale up to max 6
  - P99 latency > 2000ms → scale up (includes FX + compliance)
  - Concurrency: 300 req/s per replica (estimate)

Database:
  - Read replicas: 3 (primary handles writes, replicas for history queries)
  - Partitioning: remittances table by month (range partition on created_at)
  - Connection pooling: Laravel Octane with 50 connections per replica
  - Corridor_daily_limits: shard by corridor_id for write throughput

Cache:
  - FX rates: Redis, TTL 30s (updated from provider every 1s)
  - Rate locks: Redis, TTL 70s (60s lock + 10s buffer)
  - Corridor config: Redis, TTL 300s (rarely changes)
  - Beneficiary list: Redis, TTL 60s (invalidated on add/edit/delete)
  - History: Redis, TTL 120s (paginated, invalidated on new transfer)

FX Engine:
  - Dedicated service with in-memory rate cache
  - Connects to 3+ FX providers (XE, OANDA, local Syrian bank)
  - Uses median rate across providers to prevent manipulation
  - Rate updates: every 1 second during market hours
  - Fallback: cached rates with 5-min max age
```

## Caching Strategy
```php
// FX Rate Caching
public function getLiveRate(string $corridor): FXRate
{
    $cacheKey = "fx:rate:{$corridor}";
    return Cache::remember($cacheKey, 30, function () use ($corridor) {
        return $this->fxProvider->getRate($corridor);
    });
}

// Rate Lock (Redis with TTL — NOT in DB until consumed)
public function lockRate(string $corridor, float $rate, int $userId): string
{
    $lockId = Str::uuid();
    $lockKey = "fx:lock:{$lockId}";
    $data = [
        'corridor' => $corridor,
        'rate' => $rate,
        'user_id' => $userId,
        'locked_at' => now()->toIso8601String(),
        'expires_at' => now()->addSeconds(60)->toIso8601String(),
    ];
    Redis::setex($lockKey, 70, json_encode($data));
    return $lockId;
}

// Consume rate lock (on successful transfer)
public function consumeRateLock(string $lockId, int $remittanceId): void
{
    $lockKey = "fx:lock:{$lockId}";
    $data = Redis::get($lockKey);
    if (!$data) throw new FXRateExpiredException();

    Redis::del($lockKey);
    // Persist to DB for audit trail
    FXRateLog::create([
        'lock_id' => $lockId,
        'consumed_at' => now(),
        'remittance_id' => $remittanceId,
    ]);
}

// Invalidate beneficiary cache on change
Event::listen(function (BeneficiaryCreated|BeneficiaryUpdated|BeneficiaryDeleted $event) {
    Cache::forget("beneficiaries:user:{$event->userId}");
});
```

## Rate Limiting (Kong Gateway)
```yaml
rate_limits:
  remittance_send:
    user: 5/minute
    admin: 50/minute
    burst: 3
  
  remittance_fx_rate:
    user: 60/minute
    admin: 200/minute
    burst: 10
  
  remittance_fx_lock:
    user: 10/minute
    admin: 50/minute
    burst: 5
  
  remittance_history:
    user: 30/minute
    admin: 200/minute
    burst: 10
  
  remittance_beneficiary:
    user: 20/minute
    admin: 100/minute
    burst: 5
  
  remittance_recurring_create:
    user: 5/minute
    admin: 30/minute
    burst: 2
  
  request_money:
    user: 10/minute
    admin: 100/minute
    burst: 5
```

## External Integrations
```
FX Providers (rate sourcing):
  - XE.com API (primary)
  - OANDA API (secondary)
  - Syrian Central Bank daily rate (for SYP)
  - Local exchange houses (fallback)

Sanctions Screening:
  - World-Check (Refinitiv) — primary
  - OFAC SDN List (download + fuzzy match)
  - UN Sanctions List (Syria-specific)
  - EU Sanctions List

Correspondent Banking:
  - Deutsche Bank (EUR corridors)
  - JP Morgan (USD corridors)
  - Ziraat Bankasi (TRY corridor)
  - SWIFT MT103 messaging

SMS & Notifications:
  - Twilio (diaspora SMS)
  - Local Syrian telco aggregator (in-country SMS)
  - Firebase Cloud Messaging (push)
  - SendGrid (email receipts)
```
