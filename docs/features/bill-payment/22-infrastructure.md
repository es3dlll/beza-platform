# Bill Payment Infrastructure

## Deployment Architecture
```
┌───────────────────────────────────────────────────────────┐
│                    Kubernetes Cluster                     │
│                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  Laravel API  │  │  Queue       │  │  Scheduler   │   │
│  │  Replicas: 3  │  │  Workers: 3  │  │  Cron: 1     │   │
│  │  CPU: 2, RAM:4│  │  CPU: 1,RAM:2│  │  CPU: 0.5    │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  Redis Cache  │  │  MySQL       │  │  RabbitMQ    │   │
│  │  Replicas: 2  │  │  Primary+1 RO│  │  Cluster 3   │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                           │
│  ┌──────────────────────────────────────────────────┐    │
│  │  Biller API Gateway (Kong)                       │    │
│  │  - Biller-specific rate limiting                 │    │
│  │  - Circuit breaker per biller                    │    │
│  │  - Request/response logging                      │    │
│  └──────────────────────────────────────────────────┘    │
│                                                           │
│  ┌──────────────────────────────────────────────────┐    │
│  │  CSV Batch Engine (Sidekiq/Background Job)       │    │
│  │  - FTP/SFTP poller for incoming files            │    │
│  │  - CSV parser + validator                        │    │
│  │  - Record matcher (customer ID → user)           │    │
│  └──────────────────────────────────────────────────┘    │
└───────────────────────────────────────────────────────────┘
```

## Scaling Strategy
```
Bill Payment API:
  - HPA: CPU > 70% OR memory > 75% → scale up to max 6
  - P99 latency > 2000ms → scale up
  - Concurrency: 300 req/s per replica (estimate)

Database:
  - Read replicas: 1 (primary handles writes)
  - biller_connection_logs: partitioned by month (high write volume)
  - Connection pooling via Laravel Octane

Cache:
  - Biller catalog: TTL 3600s (invalidated on biller status change)
  - Customer ID format cache: TTL 86400s (rarely changes)
  - Recent bill for customer: TTL 30s (refetched on pay)
  - Biller API rate limit counters: TTL = window duration (Redis)
```

## Biller API Gateway (Kong)
```yaml
# Kong configuration for biller API routing
services:
  - name: biller-api-gateway
    host: biller-api-gateway.internal
    port: 8080
    routes:
      - name: peed-fetch
        paths: [/api/billers/peed/fetch]
        methods: [POST]
      - name: peed-pay
        paths: [/api/billers/peed/pay]
        methods: [POST]
      - name: syriatel-fetch
        paths: [/api/billers/syriatel/fetch]
        methods: [POST]
      # ... per-biller routes

plugins:
  - name: rate-limiting
    config:
      minute: 100  # per biller endpoint
      policy: local

  - name: circuit-breaker
    config:
      threshold: 5  # 5 failures in 60s → open circuit
      timeout: 30   # 30s before half-open
      half_open_requests: 3
```

## External Dependencies
```
┌─────────────────────┬──────────────┬──────────────┬──────────────┐
│ Biller              │ Protocol     │ Auth Method  │ SLA          │
├─────────────────────┼──────────────┼──────────────┼──────────────┤
│ PEED API            │ REST/HTTPS   │ API Key +    │ 99.5%        │
│                     │              │ HMAC-SHA256  │              │
│ Damascus Water API  │ REST/HTTPS   │ API Key      │ 99.0%        │
│ Syriatel API        │ SOAP/XML     │ Client Cert  │ 99.9%        │
│                     │              │ + Username   │              │
│ MTN API             │ REST/HTTPS   │ OAuth2       │ 99.9%        │
│ Syria Telecom API   │ REST/HTTPS   │ API Key      │ 99.0%        │
│ Aya Internet API    │ REST/HTTPS   │ Basic Auth   │ 98.0%        │
│ Saman Internet API  │ REST/HTTPS   │ Basic Auth   │ 98.0%        │
│ Gov CSV FTP         │ SFTP         │ SSH Key      │ Batch daily  │
│ University CSV FTP  │ FTP/S        │ Username/PW  │ Weekly       │
└─────────────────────┴──────────────┴──────────────┴──────────────┘
```

## Rate Limiting (Kong Gateway)
```yaml
rate_limits:
  bill_fetch:
    user: 20/minute
    ip: 60/minute
    burst: 5

  bill_pay:
    user: 10/minute
    ip: 30/minute
    burst: 3

  bill_history:
    user: 30/minute
    burst: 10

  bill_schedule:
    user: 10/minute
    burst: 3

  biller_api_outgoing:
    per_biller: 300/minute
    burst: 20
```
