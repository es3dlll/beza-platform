# Wallet Monitoring

## Key Metrics (Prometheus)

### Business Metrics
```prometheus
# Total wallets by currency
beza_wallets_total{currency="SYP"} 100000
beza_wallets_total{currency="USD"} 45000

# Active wallets (30d)
beza_active_wallets_total 65000

# Transaction volume per minute
beza_transaction_volume_total{type="send"} 2500000
beza_transaction_volume_total{type="receive"} 2500000
beza_transaction_volume_total{type="cash_in"} 5000000
beza_transaction_volume_total{type="cash_out"} 3000000

# Transaction count per minute
rate(beza_transactions_total[1m]) 150

# Fee revenue per minute
rate(beza_fee_revenue_total{type="transfer"}[1m]) 125000

# Average transaction value
beza_avg_transaction_value{type="send"} 25000
```

### Technical Metrics
```prometheus
# API latency (ms)
beza_api_duration_ms{endpoint="/wallet/balance", quantile="0.5"} 45
beza_api_duration_ms{endpoint="/wallet/balance", quantile="0.95"} 120
beza_api_duration_ms{endpoint="/wallet/balance", quantile="0.99"} 350

beza_api_duration_ms{endpoint="/wallet/transfer/send", quantile="0.5"} 320
beza_api_duration_ms{endpoint="/wallet/transfer/send", quantile="0.95"} 850
beza_api_duration_ms{endpoint="/wallet/transfer/send", quantile="0.99"} 2000

# API error rate
rate(beza_api_errors_total{endpoint="/wallet/transfer/send"}[5m]) 0.02

# Queue depth
beza_queue_depth{queue="transfer-notifications"} 150
beza_queue_depth{queue="fraud-velocity-check"} 12

# Database
beza_db_connections_active 15
beza_db_query_duration_ms{query_type="select", table="wallets"} 3
beza_db_query_duration_ms{query_type="insert", table="wallet_transactions"} 12

# CFE
beza_cfe_hold_duration_ms{quantile="0.95"} 200
beza_cfe_posting_duration_ms{quantile="0.95"} 350
```

## Grafana Dashboard: Wallet Overview

### Row 1: Key Figures
```
┌─────────────┬──────────────┬──────────────┬──────────────┐
│ Active      │ Transaction  │ Success Rate │ Avg Txn Value│
│ Wallets     │ Volume (24h) │              │              │
│ 65,000      │ 215M SYP     │ 99.7%        │ 18,500 SYP   │
└─────────────┴──────────────┴──────────────┴──────────────┘
```

### Row 2: Transaction Volume (Time Series)
```
[Line Chart: 24h of transaction volume by type]
X: Time (hourly)
Y: Volume (SYP)
Series: Send, Receive, Cash-in, Cash-out, Bills
```

### Row 3: API Performance
```
[Heatmap: P99 latency by endpoint]
X: Time (hourly)
Y: Endpoint (/balance, /send, /history, /cash-in)
Color: Latency (green < 500ms, yellow 500-2000ms, red > 2000ms)
```

### Row 4: Error Tracking
```
[Bar Chart: Error count by type]
X: Error type (insufficient_balance, invalid_recipient, limit_exceeded, timeout)
Y: Count (24h)

[Table: Top 10 failing requests]
Columns: Time, User, Endpoint, Error, Response
```

### Row 5: Queue Health
```
[Gauge: Queue depth for transfer-notifications]
Threshold: green < 100, yellow 100-1000, red > 1000

[Gauge: Queue age for fraud-velocity-check]
Threshold: green < 5s, yellow 5-30s, red > 30s
```

## Alert Rules (Prometheus)
```yaml
groups:
  - name: wallet_alerts
    rules:
      - alert: HighErrorRate
        expr: rate(beza_api_errors_total{endpoint="/wallet/transfer/send"}[5m]) > 0.05
        for: 2m
        annotations:
          summary: "Transfer endpoint error rate > 5%"
          action: "Check /wallet/transfer/send logs and CFE service health"

      - alert: HighLatency
        expr: beza_api_duration_ms{endpoint="/wallet/transfer/send", quantile="0.99"} > 5000
        for: 5m
        annotations:
          summary: "Transfer P99 latency > 5s"
          action: "Check CFE performance and database load"

      - alert: QueueBacklog
        expr: beza_queue_depth{queue="transfer-notifications"} > 10000
        for: 5m
        annotations:
          summary: "Transfer notification queue backlog > 10,000"
          action: "Scale up notification workers"

      - alert: FailedTransactionsSpike
        expr: rate(beza_transactions_failed_total[15m]) > rate(beza_transactions_failed_total[1h]) * 3
        for: 5m
        annotations:
          summary: "Spike in failed transactions"
          action: "Investigate recent deployment or external dependency"
```
