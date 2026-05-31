# Wallet Infrastructure

## Deployment Architecture
```
┌───────────────────────────────────────────────────────────┐
│                    Kubernetes Cluster                     │
│                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  Laravel API  │  │  Queue       │  │  Scheduler   │   │
│  │  Replicas: 3  │  │  Workers: 5  │  │  Cron: 1     │   │
│  │  CPU: 2, RAM:4│  │  CPU: 1,RAM:2│  │  CPU: 0.5    │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  Redis Cache  │  │  MySQL       │  │  RabbitMQ    │   │
│  │  Replicas: 2  │  │  Primary+2 RO│  │  Cluster 3   │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                           │
└───────────────────────────────────────────────────────────┘
```

## Scaling Strategy
```
Wallet API:
  - HPA: CPU > 70% OR memory > 75% → scale up to max 10
  - P99 latency > 1000ms → scale up
  - Concurrency: 500 req/s per replica (estimate)

Database:
  - Read replicas: 2 (primary handles writes)
  - Connection pooling: PgBouncer equivalent (Laravel Octane)
  - Sharding: Future consideration (by tenant_id range)

Cache:
  - Wallet balance: TTL 30s (invalidated on any transaction)
  - Transaction history: TTL 60s (invalidated on new transaction)
  - Rate limits: TTL = window duration
  - Device sessions: TTL = session duration
```

## Caching Strategy
```php
// Balance Caching
public function getCachedBalance(int $userId, Currency $currency): BalanceDTO
{
    $cacheKey = "wallet:balance:{$userId}:{$currency->value}";
    return Cache::remember($cacheKey, 30, function () use ($userId, $currency) {
        return $this->calculateBalance($userId, $currency);
    });
}

// Invalidate on any wallet transaction
Event::listen(function (WalletCredited|WalletDebited $event) {
    Cache::forget("wallet:balance:{$event->userId}:{$event->currency}");
});

// Transaction History Caching (paginated)
public function getCachedTransactions(int $userId, int $page, int $perPage): Collection
{
    $cacheKey = "wallet:txns:{$userId}:{$page}:{$perPage}";
    return Cache::remember($cacheKey, 60, function () use ($userId, $page, $perPage) {
        return $this->fetchTransactions($userId, $page, $perPage);
    });
}
```

## Rate Limiting (Kong Gateway)
```yaml
rate_limits:
  wallet_balance_read:
    user: 100/minute
    admin: 500/minute
    burst: 20
  
  wallet_transfer_send:
    user: 10/minute
    admin: 100/minute
    burst: 5
  
  wallet_transaction_history:
    user: 30/minute
    admin: 200/minute
    burst: 10
  
  wallet_cash_in:
    user: 5/minute
    agent: 30/minute
    burst: 3
```
