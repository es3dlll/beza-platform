# Bill Payment Monitoring

## Key Metrics (Prometheus)

### Business Metrics
```prometheus
# Total billers
beza_billers_total{status="active"} 9

# Bill payment volume per minute (SYP)
beza_bill_volume_total{biller_type="peed"} 52100000
beza_bill_volume_total{biller_type="syriatel"} 28500000
beza_bill_volume_total{biller_type="mtn"} 19800000
beza_bill_volume_total{biller_type="damascus_water"} 3200000
beza_bill_volume_total{biller_type="syria_telecom"} 1800000
beza_bill_volume_total{biller_type="aya_internet"} 8400000
beza_bill_volume_total{biller_type="saman_internet"} 5200000
beza_bill_volume_total{biller_type="government_fees"} 400000
beza_bill_volume_total{biller_type="university_fees"} 1200000

# Transaction count per minute
rate(beza_bill_transactions_total[1m]) 70

# Fee revenue per minute
rate(beza_bill_fee_revenue_total[1m]) 18500

# Average bill value
beza_avg_bill_value{biller_type="peed"} 42500
beza_avg_bill_value{biller_type="syriatel"} 29000

# Bill fetch vs pay ratio
beza_bill_fetch_to_pay_ratio 0.65
```

### Technical Metrics
```prometheus
# API latency (ms)
beza_api_duration_ms{endpoint="/bills/fetch", quantile="0.5"} 280
beza_api_duration_ms{endpoint="/bills/fetch", quantile="0.95"} 850
beza_api_duration_ms{endpoint="/bills/fetch", quantile="0.99"} 2500

beza_api_duration_ms{endpoint="/bills/pay", quantile="0.5"} 890
beza_api_duration_ms{endpoint="/bills/pay", quantile="0.95"} 2200
beza_api_duration_ms{endpoint="/bills/pay", quantile="0.99"} 5000

# Biller API latency (per biller)
beza_biller_api_duration_ms{biller="peed", operation="fetch", quantile="0.95"} 620
beza_biller_api_duration_ms{biller="peed", operation="pay", quantile="0.95"} 1450
beza_biller_api_duration_ms{biller="syriatel", operation="fetch", quantile="0.95"} 780
beza_biller_api_duration_ms{biller="syriatel", operation="pay", quantile="0.95"} 1800

# Biller API error rate
rate(beza_biller_api_errors_total{biller="peed"}[5m]) 0.015
rate(beza_biller_api_errors_total{biller="syriatel"}[5m]) 0.025
rate(beza_biller_api_errors_total{biller="government_fees"}[5m]) 0.0

# API error rate
rate(beza_api_errors_total{endpoint="/bills/pay"}[5m]) 0.03

# Queue depth
beza_queue_depth{queue="bill-payment"} 25
beza_queue_depth{queue="auto-pay"} 150
beza_queue_depth{queue="csv-processing"} 0

# Biller circuit breaker status
beza_biller_circuit_breaker{biller="peed", status="closed"} 1
beza_biller_circuit_breaker{biller="syriatel", status="closed"} 1
```

## Grafana Dashboard: Bill Payment Overview

### Row 1: Key Figures
```
┌──────────────┬──────────────┬──────────────┬──────────────┬──────────────┐
│ Bill         │ Volume (24h) │ Success Rate │ Avg Value    │ Fetch→Pay    │
│ Transactions │              │              │              │ Ratio        │
│ 4,275        │ 120.6M SYP   │ 98.5%        │ 28,200 SYP   │ 65%          │
└──────────────┴──────────────┴──────────────┴──────────────┴──────────────┘
```

### Row 2: Transaction Volume by Biller (Time Series)
```
[Stacked Bar Chart: 24h of bill payment volume]
X: Time (hourly)
Y: Volume (SYP)
Series: PEED (blue), Syriatel (green), MTN (orange), Water (cyan), Others (grey)
```

### Row 3: Biller API Health
```
[Table: Per-biller health status]
Columns: Biller | Fetch P95 | Pay P95 | Error Rate | Circuit Breaker | Last Downtime
Rows: PEED, Damascus Water, Syriatel, MTN, Syria Telecom, Aya, Saman, Gov, Uni
```

### Row 4: Error Tracking
```
[Pie Chart: Error distribution by type]
Types: Insufficient balance (40%), Biller timeout (25%), Invalid ID (15%),
       Already paid (10%), Biller API error (7%), Other (3%)

[Table: Top 10 failing biller requests]
Columns: Time, Biller, Operation, Customer ID, Error, Duration
```

### Row 5: Auto-pay & Schedule Health
```
[Gauge: Auto-pay success rate]
Threshold: green > 95%, yellow 85-95%, red < 85%

[Time Series: Scheduled bills count by status]
Series: Active, Overdue, Auto-pay failed, Paused
```

## Alert Rules (Prometheus)
```yaml
groups:
  - name: bill_payment_alerts
    rules:
      - alert: HighBillPaymentErrorRate
        expr: rate(beza_api_errors_total{endpoint="/bills/pay"}[5m]) > 0.05
        for: 2m
        annotations:
          summary: "Bill payment endpoint error rate > 5%"
          action: "Check biller APIs, wallet service, and payment pipeline"

      - alert: BillerApiDown
        expr: rate(beza_biller_api_errors_total[5m]) > 0.1
        for: 5m
        annotations:
          summary: "{{ $labels.biller }} API error rate > 10%"
          action: "Check {{ $labels.biller }} API status, circuit breaker status"

      - alert: BillerHighLatency
        expr: beza_biller_api_duration_ms{operation="pay", quantile="0.95"} > 3000
        for: 5m
        annotations:
          summary: "{{ $labels.biller }} pay P95 latency > 3s"
          action: "Investigate biller API performance degradation"

      - alert: BillPaymentQueueBacklog
        expr: beza_queue_depth{queue="bill-payment"} > 1000
        for: 5m
        annotations:
          summary: "Bill payment queue backlog > 1,000"
          action: "Scale up payment workers"

      - alert: AutoPayFailureRate
        expr: rate(beza_auto_pay_failures_total[1d]) > 0.1
        for: 1h
        annotations:
          summary: "Auto-pay failure rate > 10% today"
          action: "Check auto-pay pipeline, user wallet balances"

      - alert: CsvBatchProcessingFailure
        expr: beza_csv_batch_processing_failures > 0
        annotations:
          summary: "CSV batch processing failed"
          action: "Check CSV file format, FTP connection, parser errors"

      - alert: BillerCircuitBreakerOpen
        expr: beza_biller_circuit_breaker{status="open"} > 0
        for: 1m
        annotations:
          summary: "{{ $labels.biller }} circuit breaker OPEN"
          action: "Investigate biller outage, notify biller contact"
```
