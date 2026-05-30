# Cards Monitoring

## Key Metrics (Prometheus)

### Business Metrics
```prometheus
# Total cards issued by type
beza_cards_total{type="virtual"} 100000
beza_cards_total{type="physical"} 20000

# Cards by status
beza_cards_by_status{status="active"} 95000
beza_cards_by_status{status="frozen"} 3000
beza_cards_by_status{status="closed"} 15000
beza_cards_by_status{status="lost"} 500

# Transaction volume per minute
beza_card_txn_volume_total{type="purchase"} 15000000
beza_card_txn_volume_total{type="atm"} 3000000
beza_card_txn_volume_total{type="refund"} 500000

# Transaction count per minute
rate(beza_card_txns_total[1m]) 250

# Authorization success rate
rate(beza_card_auth_total{result="approved"}[5m]) /
rate(beza_card_auth_total[5m]) 0.97

# Interchange revenue per minute
rate(beza_card_interchange_total[1m]) 75000

# Fraud declined value
rate(beza_card_fraud_declined_total[1m]) 250000
```

### Technical Metrics
```prometheus
# Card Processor latency (ms)
beza_card_processor_duration_ms{operation="auth", quantile="0.5"} 45
beza_card_processor_duration_ms{operation="auth", quantile="0.95"} 120
beza_card_processor_duration_ms{operation="auth", quantile="0.99"} 350

beza_card_processor_duration_ms{operation="settlement", quantile="0.5"} 500
beza_card_processor_duration_ms{operation="settlement", quantile="0.99"} 2000

# API latency
beza_api_duration_ms{endpoint="/cards/create", quantile="0.95"} 800
beza_api_duration_ms{endpoint="/cards/{id}/freeze", quantile="0.95"} 300

# ISO 8583 message latency
beza_iso8583_duration_ms{type="0100_auth", quantile="0.95"} 150
beza_iso8583_duration_ms{type="0420_clearing", quantile="0.95"} 5000

# HSM response time
beza_hsm_duration_ms{operation="pin_verify", quantile="0.95"} 50
beza_hsm_duration_ms{operation="cvv_generate", quantile="0.95"} 30

# Database
beza_db_query_duration_ms{query_type="insert", table="card_transactions"} 15
beza_db_query_duration_ms{query_type="select", table="cards"} 5
```

## Grafana Dashboard: Cards Overview

### Row 1: Key Figures
```
┌─────────────┬──────────────┬──────────────┬──────────────┐
│ Active      │ Auth Volume  │ Auth Success │ Frauds       │
│ Cards       │ (24h)        │ Rate (24h)   │ Blocked (24h)│
│ 95,000      │ 85M SYP      │ 97.2%        │ 125          │
└─────────────┴──────────────┴──────────────┴──────────────┘
```

### Row 2: Auth Activity (Time Series)
```
[Line Chart: 24h of card auths by result]
X: Time (hourly)
Y: Count
Series: Approved, Declined (by reason: limit, fraud, frozen, insufficient)
```

### Row 3: Card Processor Performance
```
[Heatmap: P99 latency by auth origin]
X: Time (hourly)
Y: Origin (local_switch, international_sponsor, tsp_token)
Color: Latency (green < 100ms, yellow 100-300ms, red > 300ms)
```

### Row 4: Card Portfolio
```
[Pie Chart: Cards by status]
Slices: Active (85%), Frozen (3%), Closed (10%), Lost (1%), Expired (1%)

[Bar Chart: Cards issued per day (last 30 days)]
X: Date
Y: Count
Series: Virtual, Physical
```

### Row 5: Top Decline Reasons
```
[Bar Chart: Decline reasons (24h)]
X: Reason (limit_exceeded, insufficient_balance, card_frozen, fraud_declined, invalid_cvv)
Y: Count
```

## Alert Rules (Prometheus)
```yaml
groups:
  - name: card_alerts
    rules:
      - alert: HighAuthDeclineRate
        expr: rate(beza_card_auth_total{result="declined"}[5m]) / rate(beza_card_auth_total[5m]) > 0.10
        for: 2m
        annotations:
          summary: "Card auth decline rate > 10%"
          action: "Check merchant, limits, fraud rules; review decline reasons"

      - alert: CardProcessorLatency
        expr: beza_card_processor_duration_ms{operation="auth", quantile="0.99"} > 500
        for: 5m
        annotations:
          summary: "Card processor P99 latency > 500ms"
          action: "Check switch connection, HSM response, processor load"

      - alert: HsmUnreachable
        expr: beza_hsm_duration_ms{operation="pin_verify"} == 0
        for: 1m
        annotations:
          summary: "HSM not responding"
          action: "P0 incident — check HSM appliance, network, reboot"

      - alert: FraudSpike
        expr: rate(beza_card_fraud_declined_total[15m]) > rate(beza_card_fraud_declined_total[1h]) * 5
        for: 5m
        annotations:
          summary: "Fraud decline spike — possible BIN attack"
          action: "Check fraud rules, review declined patterns, consider BIN rate-limit"

      - alert: SettlementDelay
        expr: time() - beza_card_settlement_last_timestamp > 3600
        annotations:
          summary: "No settlement batch in last hour"
          action: "Check clearing file processing, CFE connection"

      - alert: QueueBacklog
        expr: beza_queue_depth{queue="card-settlement"} > 10000
        for: 5m
        annotations:
          summary: "Card settlement queue backlog > 10,000"
          action: "Scale settlement workers, check CFE"
```
