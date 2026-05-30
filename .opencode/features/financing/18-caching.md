# استراتيجية التخزين المؤقت — Caching Strategy

## Cache Layers
| Layer | Technology | Use Case | TTL |
|-------|------------|----------|-----|
| L1 (In-Memory) | Node process memory | Hot product catalog, static config | 5 minutes |
| L2 (Distributed) | Redis | Credit scores, application status, session data | Variable |
| L3 (CDN) | CloudFront | Contract PDFs, static assets, Sharia badges | 24 hours |
| L4 (DB Read Replica) | PostgreSQL replica | Reporting queries, historical data | N/A |

## Caching Rules

### Product Catalog
```typescript
// Cache product definitions (rarely changes)
// Key: financing:products:{lang}
// TTL: 300 seconds
// Invalidate: on product create/update/status change
```

### Credit Score
```typescript
// Cache user's credit score
// Key: financing:credit-score:{userId}
// TTL: 86400 seconds (24 hours) — stale-while-revalidate
// Invalidate: on new application, repayment, or score recalculation
// 
// Stale-while-revalidate:
// - If < 24h old: return cached, refresh in background
// - If > 24h and < 48h: return cached, trigger recalculate, return stale
// - If > 48h: trigger recalculate, wait for result
```

### Application Status
```typescript
// Cache application state (reduces DB load)
// Key: financing:application:{applicationId}
// TTL: 60 seconds
// Invalidate: on status change, document upload
// Notes: Short TTL because status changes in real-time
```

### Active Loans Summary
```typescript
// Cache user's active loans
// Key: financing:active:{userId}
// TTL: 30 seconds
// Invalidate: on disbursement, repayment, status change
```

### Repayment Schedule
```typescript
// Cache full schedule for a contract
// Key: financing:schedule:{contractId}
// TTL: 300 seconds
// Invalidate: on payment, restructure
// 
// For large schedules (90+ installments):
// - Cache list of installments
// - Individual installment status fetched live from DB
```

### Rate Limits
```typescript
// API rate limiting counters
// Key: financing:ratelimit:{userId}:{endpoint}
// TTL: sliding window (1 minute)
// Storage: Redis sorted set
```

### Session & Idempotency
```typescript
// Idempotency keys for mutations
// Key: financing:idempotency:{idempotencyKey}
// TTL: 24 hours
// Storage: Redis string (stores response body)
```

## Cache Performance Targets
| Metric | Target |
|--------|--------|
| Cache hit ratio (product catalog) | > 99% |
| Cache hit ratio (credit score) | > 85% |
| Cache hit ratio (application status) | > 80% |
| Average response time (cached) | < 20ms |
| Average response time (uncached) | < 200ms |
| Redis memory budget | 2 GB |
| Eviction policy | allkeys-lru |

## Cache Invalidation Strategy
```typescript
// Pattern: Cache-aside with manual invalidation

interface CacheInvalidation {
  onEvent: string;         // Kafka event that triggers invalidation
  keysToInvalidate: string[];
}

const invalidationRules: CacheInvalidation[] = [
  {
    onEvent: 'financing.payment.received',
    keysToInvalidate: [
      'financing:active:{userId}',
      'financing:schedule:{contractId}',
      'financing:credit-score:{userId}'
    ]
  },
  {
    onEvent: 'financing.application.decided',
    keysToInvalidate: [
      'financing:application:{applicationId}'
    ]
  },
  {
    onEvent: 'financing.disbursement.completed',
    keysToInvalidate: [
      'financing:active:{userId}',
      'financing:application:{applicationId}'
    ]
  }
];
```

## Redis Configuration
```yaml
redis:
  host: ${REDIS_HOST}
  port: 6379
  password: ${REDIS_PASSWORD}
  cluster:
    enabled: false  # Single instance for now
    nodes: []       # Add cluster nodes when scaling
  keyPrefix: 'beza:financing:'
  options:
    maxRetriesPerRequest: 3
    enableReadyCheck: true
    retryStrategy: (times) => Math.min(times * 50, 2000)
  monitor:
    slowLogThreshold: 100  # Log queries taking > 100ms
```
