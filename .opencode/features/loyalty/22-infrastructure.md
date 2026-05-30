# Loyalty Infrastructure

## Deployment Architecture
```
┌───────────────────────────────────────────────────────────┐
│                    Kubernetes Cluster                     │
│                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  Loyalty API  │  │  Batch       │  │  Scheduler   │   │
│  │  Replicas: 2  │  │  Workers: 3  │  │  Cron: 1     │   │
│  │  CPU: 1, RAM:2│  │  CPU: 2,RAM:4│  │  CPU: 0.5    │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  Redis Cache  │  │  MySQL       │  │  RabbitMQ    │   │
│  │  Replicas: 2  │  │  Primary+1 RO│  │  Cluster 3   │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                           │
└───────────────────────────────────────────────────────────┘
```

## Scaling Strategy
```
Loyalty API:
  - HPA: CPU > 70% → scale to max 5
  - P99 latency target: < 200ms (most operations are simple reads)
  - Concurrency: 500 req/s per replica

Batch Workers:
  - Tier upgrade worker: runs daily at 02:00, processes all users
  - Points expiry worker: runs daily at 02:30
  - Airtime settlement: runs daily at 03:00
  - Gift card settlement: runs weekly Saturday 03:00

Database:
  - loyalty_points: partitioned by month
  - Points balance cache in Redis (TTL: 60s, invalidated on earn/redeem)
  - Reward catalog cached in Redis (TTL: 1h, invalidated on update)
```

## Caching Strategy
```php
// Points balance cache
public function getCachedBalance(int $userId): int
{
    $cacheKey = "loyalty:balance:{$userId}";
    return Cache::remember($cacheKey, 60, function () use ($userId) {
        return $this->pointsRepo->getBalance($userId);
    });
}

// Invalidate on any points change
Event::listen(function (PointsEarned|PointsRedeemed|PointsExpired $event) {
    Cache::forget("loyalty:balance:{$event->userId}");
});

// Tier info cache (changes daily)
public function getCachedTier(int $userId): TierProgress
{
    $cacheKey = "loyalty:tier:{$userId}";
    return Cache::remember($cacheKey, 3600, function () use ($userId) {
        return $this->tierService->getTierProgress($userId);
    });
}

// Reward catalog cache
public function getCachedCatalog(): array
{
    return Cache::remember('loyalty:catalog', 3600, function () {
        return $this->catalogService->getAvailableRewards();
    });
}
```

## Rate Limiting (Kong Gateway)
```yaml
rate_limits:
  loyalty_balance:
    user: 30/minute
    burst: 10
  
  loyalty_redeem:
    user: 5/minute
    burst: 3
  
  loyalty_history:
    user: 20/minute
    burst: 10
  
  merchant_campaign_create:
    user: 3/minute
    merchant: 10/minute
```
