# Open Finance Infrastructure

## Deployment Architecture
```
┌───────────────────────────────────────────────────────────┐
│                    Kubernetes Cluster                     │
│                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  API Gateway  │  │  Portal API  │  │  Sandbox     │   │
│  │  (Kong)       │  │  Replicas: 2 │  │  Replicas: 2 │   │
│  │  Replicas: 3  │  │  CPU: 2,RAM:4│  │  CPU: 1,RAM:2│   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  Webhook     │  │  Redis       │  │  MySQL       │   │
│  │  Worker: 3   │  │  Cluster 3   │  │  Primary+2 RO│   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                           │
└───────────────────────────────────────────────────────────┘
```

## Scaling Strategy
```
API Gateway:
  - HPA: CPU > 70% OR request latency > 500ms → scale to max 10
  - P99 latency target: < 200ms
  - Concurrency: 1,000 req/s per replica

Webhook Worker:
  - HPA: Queue depth > 1,000 → scale to max 20
  - Each worker handles 50 deliveries/sec
  - Dead letter queue for failed deliveries (3+ attempts)

Sandbox:
  - Dedicated pool (isolated from production)
  - In-memory ledger (no CFE dependency)
  - Auto-reset after 24h of inactivity

Database:
  - Read replicas: 2 (api_usage_logs queries)
  - Partitioned: api_usage_logs by month
  - Archive: move usage logs > 6 months to cold storage
```

## Caching Strategy
```php
// API key cache (fast lookup)
public function getCachedApiKey(string $hash): ?ApiKey
{
    $cacheKey = "apikey:{$hash}";
    return Cache::remember($cacheKey, 3600, function () use ($hash) {
        return $this->keyRepo->findByHash($hash);
    });
}

// Rate limit counters (Redis)
public function checkRateLimit(int $developerId, string $tier): void
{
    $key = "ratelimit:{$developerId}:minute";
    $count = Redis::incr($key);
    if ($count === 1) Redis::expire($key, 60);
    // ... check against tier limits
}

// OAuth token cache
public function getCachedToken(string $hash): ?OAuthToken
{
    $cacheKey = "oauth:token:{$hash}";
    return Cache::remember($cacheKey, 600, function () use ($hash) {
        return $this->tokenRepo->findValidByHash($hash);
    });
}

// Dashboard stats cache
public function getDashboardStats(int $devId): array
{
    $cacheKey = "dashboard:{$devId}";
    return Cache::remember($cacheKey, 300, function () use ($devId) {
        return $this->portalService->calculateStats($devId);
    });
}
```

## Rate Limiting (Kong Gateway)
```yaml
rate_limits:
  of_payments:
    free: 10/minute, 1000/day
    startup: 100/minute, 10000/day
    business: 500/minute, 100000/day
    enterprise: 2000/minute, unlimited
    burst: 2x limit
  
  of_accounts:
    free: 30/minute
    startup: 200/minute
    business: 1000/minute
    enterprise: 5000/minute
  
  of_webhooks:
    free: 5/minute
    startup: 50/minute
    business: 200/minute
    enterprise: 1000/minute
  
  of_sandbox:
    all: 100/minute (no tier limits)
```

## API Gateway Configuration (Kong)
```yaml
services:
  - name: open-finance-api
    host: open-finance-svc
    port: 8080
    routes:
      - name: of-payments
        paths: ["/api/v1/of/payments"]
        methods: ["POST", "GET"]
        plugins:
          - name: rate-limiting
            config:
              policy: redis
              limits: { minute: 100 }
          - name: key-auth
            config: { key_names: ["Authorization"] }
          - name: cors
            config: { origins: ["*"], methods: ["GET", "POST", "PUT", "DELETE"] }
```
