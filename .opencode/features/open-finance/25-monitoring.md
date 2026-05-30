# Open Finance Monitoring

## Key Metrics (Prometheus)

### Business Metrics
```prometheus
# Registered developers
beza_of_developers_total 500

# Active developers (30d)
beza_of_active_developers_total 150

# API calls per minute by endpoint
rate(beza_of_api_calls_total{endpoint="/payments"}[1m]) 50
rate(beza_of_api_calls_total{endpoint="/accounts"}[1m]) 120
rate(beza_of_api_calls_total{endpoint="/webhooks"}[1m]) 200

# Payment volume per minute
rate(beza_of_payment_volume_total[1m]) 2500000

# API error rate by endpoint
rate(beza_of_api_errors_total{endpoint="/payments"}[5m]) 0.02

# Revenue per minute (API fees)
rate(beza_of_revenue_total[1m]) 5000

# Webhook delivery success rate
beza_of_webhook_success_rate 0.97
```

### Technical Metrics
```prometheus
# API latency (ms) by endpoint
beza_of_api_duration_ms{endpoint="/payments", quantile="0.5"} 120
beza_of_api_duration_ms{endpoint="/payments", quantile="0.95"} 350
beza_of_api_duration_ms{endpoint="/payments", quantile="0.99"} 800

beza_of_api_duration_ms{endpoint="/accounts/balance", quantile="0.5"} 45
beza_of_api_duration_ms{endpoint="/accounts/balance", quantile="0.99"} 200

# Rate limit hits
rate(beza_of_rate_limit_hits_total[1h]) 5

# Webhook queue depth
beza_of_webhook_queue_depth 25

# Active API keys by environment
beza_of_active_keys_total{environment="sandbox"} 300
beza_of_active_keys_total{environment="production"} 150
```

## Grafana Dashboard: Open Finance Overview

### Row 1: Key Figures
```
┌─────────────────┬─────────────────┬─────────────────┬─────────────────┐
│ Registered      │ Active          │ API Calls       │ Payment Volume  │
│ Developers      │ Developers (30d)│ (24h)           │ (24h)           │
│ 500             │ 200             │ 150,000         │ 50M SYP         │
└─────────────────┴─────────────────┴─────────────────┴─────────────────┘
```

### Row 2: API Performance
```
[Line Chart: P99 latency by endpoint over 24h]
X: Time (hourly)
Y: Latency (ms)
Series: /payments, /accounts, /webhooks, /agents

[Bar Chart: Request count by endpoint]
X: Endpoint
Y: Count (24h)
```

### Row 3: Error Tracking
```
[Bar Chart: Error count by code]
X: Error code (INVALID_API_KEY, RATE_LIMIT, etc.)
Y: Count (24h)

[Table: Top 10 failing requests]
Columns: Time, Developer, Endpoint, Error, Latency
```

### Row 4: Developer Activity
```
[Table: Top 10 developers by call volume]
Columns: Developer, Company, Tier, Calls (24h), Errors, Latency
```

## Alert Rules (Prometheus)
```yaml
groups:
  - name: open_finance_alerts
    rules:
      - alert: HighApiErrorRate
        expr: rate(beza_of_api_errors_total{endpoint="/payments"}[5m]) > 0.05
        for: 2m
        annotations:
          summary: "Payment API error rate > 5%"
          action: "Check payment service and CFE health"

      - alert: HighApiLatency
        expr: beza_of_api_duration_ms{endpoint="/payments", quantile="0.99"} > 2000
        for: 5m
        annotations:
          summary: "Payment API P99 latency > 2s"
          action: "Check API gateway and downstream services"

      - alert: WebhookBacklog
        expr: beza_of_webhook_queue_depth > 1000
        for: 5m
        annotations:
          summary: "Webhook delivery queue > 1,000"
          action: "Scale webhook workers"

      - alert: RateLimitSpike
        expr: rate(beza_of_rate_limit_hits_total[1h]) > 50
        for: 5m
        annotations:
          summary: "Rate limit hits > 50/hour"
          action: "Check for abusive developer or misconfigured client"
```
