# Observability Stack

## Three Pillars

### 1. Logging (Elasticsearch + Filebeat + Kibana)
```
Log Levels: DEBUG | INFO | WARN | ERROR | CRITICAL
Log Format: JSON structured (all services standard)
Required Fields: timestamp, level, service_name, correlation_id, user_id, tenant_id, action, result, duration_ms
Storage: Hot (7d) → Warm (30d) → Cold (90d) → Delete
Index Pattern: beza-{service}-{YYYY.MM.DD}

Critical Alerts:
  ERROR rate > 5% in 5m → PagerDuty
  CRITICAL any → PagerDuty + SMS
  Laravel Exception → Slack #alerts-errors
```

### 2. Metrics (Prometheus + Grafana)
```
Instrumentation: Laravel (spatie/laravel-prometheus), Flutter, Infrastructure
Collection: 15s scrape interval
Retention: 30d Prometheus, 1yr Thanos (S3)

Key Metrics (RED Method):
  Rate: Requests/sec, Error rate, Transaction rate
  Errors: HTTP 5xx, Queue failures, Payment failures
  Duration: P50/P95/P99 latency, DB query time, External API

Business Metrics:
  MAU (Monthly Active Users)
  DAU (Daily Active Users)
  Transaction Volume (SYP, USD)
  Revenue (fees, spread, MDR)
  Agent/Merchant onboarding rate
  Conversion funnel (Registration → First Txn → Repeat)
```

### 3. Tracing (Jaeger / OpenTelemetry)
```
Sampling: 
  Head-based: 100% for financial transactions, 10% for API calls
  Tail-based: Error samples always captured

Spans:
  Transaction: end-to-end financial flow
  HTTP Request: API Gateway → Service → DB → Queue
  Queue Job: consumer processing time
  External API: partner timeouts and failures

Context Propagation:
  W3C Trace Context (traceparent header)
  Correlation ID: x-correlation-id (generated at API Gateway)
  Baggage: user_id, tenant_id, device_id
```

## Alerting Rules
```
P0 (Critical, 5min response):
  [Fire] Transaction failure rate > 2%
  [Fire] Payment gateway unavailable
  [Fire] Database replicas lag > 30s
  [Fire] Fraud detection system offline

P1 (High, 15min response):
  [Fire] API P99 latency > 5s
  [Fire] Queue backlog > 10,000 messages
  [Fire] Login error rate > 10%
  [Fire] Agent balance discrepancies detected

P2 (Medium, 1hr response):
  [Warn] CPU > 80% for 10min
  [Warn] Disk > 85%
  [Warn] Queue consumer lag > 1000
  [Warn] SSL certificate expires < 14 days

P3 (Low, 24hr response):
  [Info] New user registration anomaly
  [Info] Unusual FX rate deviation
  [Info] Feature adoption drop
```

## Dashboard Structure (Grafana)
```
Folder: Beza Platform
  1. System Overview: All services health, request volume, error rate
  2. Financial Operations: Transaction volume, settlement status, fee revenue
  3. Agent Network: Active agents, cash-in/out volume, float levels
  4. User Experience: Login rate, session duration, feature adoption
  5. Fraud & Risk: Fraud rate, risk distribution, model performance
  6. Infrastructure: CPU/mem/disk per service, DB queries, queue depth
  7. Business: Revenue, MAU, DAU, conversion funnel, retention
  8. Security: Auth failures, suspicious IPs, device anomalies
```

## SLA/SLO Targets
```
Service         Availability    Latency P99     Error Budget
API Gateway         99.99%          500ms         Monthly 4.3m
Transfer Service    99.99%         2000ms         Monthly 4.3m
Auth Service        99.99%          500ms         Monthly 4.3m
Agent Service       99.95%         3000ms         Weekly 5m
Analytics API       99.50%         5000ms         Weekly 1hr
Internal APIs       99.90%         1000ms         Weekly 10m
Database            99.99%          100ms         Monthly 4.3m
Event Bus           99.99%           50ms         Monthly 4.3m
```
