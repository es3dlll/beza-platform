# Remittance Monitoring

## Key Metrics (Prometheus)

### Business Metrics
```prometheus
# Remittance volume by corridor
beza_remittance_volume_total{corridor="EUR_DE->SYP"} 45000
beza_remittance_volume_total{corridor="USD_US->SYP"} 62500
beza_remittance_volume_total{corridor="SYP_SY->SYP"} 50000000
beza_remittance_volume_total{corridor="TRY_TR->SYP"} 75000

# Remittance count per minute
rate(beza_remittances_total[1m]) 45

# Revenue per corridor
beza_remittance_fee_income{corridor="EUR_DE->SYP"} 675
beza_remittance_fx_income{corridor="EUR_DE->SYP"} 8910000

# Active diaspora senders (30d)
beza_active_diaspora_senders_total{country="DE"} 15000
beza_active_diaspora_senders_total{country="US"} 8500
beza_active_diaspora_senders_total{country="SE"} 4200

# Average remittance size
beza_avg_remittance_amount{corridor="EUR_DE->SYP"} 300
beza_avg_remittance_amount{corridor="USD_US->SYP"} 450

# Recurring stats
beza_recurring_transfers_total{status="active"} 3200
beza_recurring_execution_rate{status="success"} 0.97
beza_recurring_execution_rate{status="failed"} 0.03

# FX rate lock stats
beza_fx_locks_total 12500
beza_fx_locks_consumed 11800
beza_fx_abandonment_rate 0.056
```

### Technical Metrics
```prometheus
# API latency (ms)
beza_api_duration_ms{endpoint="/remittance/send", quantile="0.5"} 450
beza_api_duration_ms{endpoint="/remittance/send", quantile="0.95"} 1200
beza_api_duration_ms{endpoint="/remittance/send", quantile="0.99"} 3500

beza_api_duration_ms{endpoint="/remittance/fx/rate", quantile="0.5"} 35
beza_api_duration_ms{endpoint="/remittance/fx/rate", quantile="0.95"} 120
beza_api_duration_ms{endpoint="/remittance/fx/rate", quantile="0.99"} 300

beza_api_duration_ms{endpoint="/remittance/fx/lock", quantile="0.5"} 20
beza_api_duration_ms{endpoint="/remittance/fx/lock", quantile="0.95"} 50
beza_api_duration_ms{endpoint="/remittance/fx/lock", quantile="0.99"} 150

# API error rate
rate(beza_api_errors_total{endpoint="/remittance/send"}[5m]) 0.015

# Compliance screening latency
beza_compliance_screening_duration_ms{quantile="0.5"} 150
beza_compliance_screening_duration_ms{quantile="0.95"} 450
beza_compliance_screening_duration_ms{quantile="0.99"} 1200

# Queue depth
beza_queue_depth{queue="recipient-notifications"} 85
beza_queue_depth{queue="compliance-screening"} 12
beza_queue_depth{queue="recurring-execution"} 3

# FX rate freshness
beza_fx_rate_age_seconds{provider="xe"} 0.5
beza_fx_rate_age_seconds{provider="oanda"} 1.2
beza_fx_rate_age_seconds{provider="local"} 30

# End-to-end transfer duration
beza_remittance_e2e_duration_ms{corridor="SYP_SY->SYP", quantile="0.95"} 1500
beza_remittance_e2e_duration_ms{corridor="EUR_DE->SYP", quantile="0.95"} 5000
beza_remittance_e2e_duration_ms{corridor="USD_US->SYP", quantile="0.95"} 5500
```

## Grafana Dashboard: Remittance Overview

### Row 1: Key Figures
```
┌────────────┬──────────────┬──────────────┬──────────────┬──────────────┐
│ Active     │ Remittance   │ Success Rate │ Avg Txn Value│ Revenue      │
│ Senders    │ Volume (24h) │              │              │ (24h)        │
│ 32,500     │ $2.1M        │ 99.2%        │ $325         │ $31,500      │
└────────────┴──────────────┴──────────────┴──────────────┴──────────────┘
```

### Row 2: Remittance Volume by Corridor
```
[Stacked Bar Chart: 24h of remittance volume by corridor]
X: Time (hourly)
Y: Volume (USD equivalent)
Series: EUR_DE->SYP, USD_US->SYP, SYP_SY->SYP, TRY_TR->SYP, etc.
```

### Row 3: FX Performance
```
[Line Chart: FX rate + mid-market rate over time]
X: Time (hourly)
Y: Rate (SYP per EUR/USD)
Series: Beza Rate (EUR→SYP), Mid-Market Rate, Spread %

[Gauge: Rate lock abandonment rate]
Threshold: green < 3%, yellow 3-8%, red > 8%
Current: 5.6%
```

### Row 4: Compliance Queue
```
[Table: Compliance queue items]
Columns: Priority, User, Type, Amount, Time, Status
P0: 0 items
P1: 3 items (oldest: 12 min)
P2: 8 items
P3: 15 items
P4: 42 items
```

### Row 5: Recurring Transfers Health
```
[Line Chart: Recurring execution success rate over 7 days]
X: Date
Y: Success Rate %
Threshold: 95%

[Gauge: Recurring transfers active/total]
Active: 3,200 / Total: 4,100
```

## Alert Rules (Prometheus)
```yaml
groups:
  - name: remittance_alerts
    rules:
      - alert: HighRemittanceErrorRate
        expr: rate(beza_api_errors_total{endpoint="/remittance/send"}[5m]) > 0.05
        for: 2m
        annotations:
          summary: "Remittance send endpoint error rate > 5%"
          action: "Check remittance service, FX engine, and compliance service"

      - alert: HighRemittanceLatency
        expr: beza_api_duration_ms{endpoint="/remittance/send", quantile="0.99"} > 5000
        for: 5m
        annotations:
          summary: "Remittance P99 latency > 5s"
          action: "Check FX engine response time, compliance screening, and CFE"

      - alert: FXRateStale
        expr: beza_fx_rate_age_seconds{provider="xe"} > 10
        for: 1m
        annotations:
          summary: "XE.com FX rate provider data stale > 10s"
          action: "Check FX provider API, failover to OANDA"

      - alert: RecurringExecutionFailure
        expr: beza_recurring_execution_rate{status="failed"} > 0.10
        for: 30m
        annotations:
          summary: "Recurring transfer failure rate > 10%"
          action: "Check recurring execution service, sender balances"

      - alert: ComplianceQueueBacklog
        expr: beza_queue_depth{queue="compliance-screening"} > 500
        for: 5m
        annotations:
          summary: "Compliance screening queue backlog > 500"
          action: "Scale up compliance workers, check screening service"

      - alert: SanctionsMatch
        expr: rate(beza_sanctions_matches_total[5m]) > 0
        for: 1m
        annotations:
          summary: "Sanctions match detected"
          action: "Check compliance P0 queue immediately"

      - alert: FXAbandonmentRateHigh
        expr: beza_fx_abandonment_rate > 0.15
        for: 10m
        annotations:
          summary: "FX rate lock abandonment rate > 15%"
          action: "Possible rate arbitrage attack, investigate user patterns"

      - alert: RemittanceVolumeDrop
        expr: rate(beza_remittances_total[1h]) < rate(beza_remittances_total[24h]) * 0.3
        for: 30m
        annotations:
          summary: "Remittance volume dropped > 70% in last hour"
          action: "Check for corridor outages, API issues, or competitor action"
```
