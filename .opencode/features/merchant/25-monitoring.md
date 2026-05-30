# Merchant Monitoring

## Key Metrics (Prometheus)

### Business Metrics
```prometheus
# Total merchants by status
beza_merchants_total{status="verified"} 4200
beza_merchants_total{status="pending"} 350
beza_merchants_total{status="suspended"} 12

# Active merchants (30d with at least 1 txn)
beza_active_merchants_total{tier="micro"} 1800
beza_active_merchants_total{tier="small"} 1200
beza_active_merchants_total{tier="mid"} 200
beza_active_merchants_total{tier="enterprise"} 50

# Transaction volume by method
beza_merchant_tpv_total{method="qr"} 15000000
beza_merchant_tpv_total{method="pos"} 8000000
beza_merchant_tpv_total{method="payment_link"} 3000000
beza_merchant_tpv_total{method="web_checkout"} 2000000

# Transaction count per minute
rate(beza_merchant_transactions_total[1m]) 45

# MDR revenue per minute
rate(beza_mdr_revenue_total[1m]) 12500

# Average transaction value by method
beza_avg_txn_value{method="qr"} 18500
beza_avg_txn_value{method="pos"} 45000
beza_avg_txn_value{method="payment_link"} 35000
beza_avg_txn_value{method="web_checkout"} 75000

# Settlement statistics
beza_settlement_total{status="completed"} 150000000
beza_settlement_count{status="completed"} 3200
beza_settlement_failed_total 5
```

### Technical Metrics
```prometheus
# QR endpoints
beza_api_duration_ms{endpoint="/merchant/qr/generate", quantile="0.5"} 120
beza_api_duration_ms{endpoint="/merchant/qr/generate", quantile="0.95"} 350
beza_api_duration_ms{endpoint="/merchant/qr/generate", quantile="0.99"} 800

# Payment link creation
beza_api_duration_ms{endpoint="/merchant/payment-link", quantile="0.5"} 80
beza_api_duration_ms{endpoint="/merchant/payment-link", quantile="0.95"} 200

# QR image serving (CDN, not API)
beza_cdn_latency{endpoint="/merchant/qr/{id}"} avg 25

# Webhook delivery
beza_webhook_delivery_duration_ms{quantile="0.5"} 350
beza_webhook_delivery_duration_ms{quantile="0.95"} 1200
beza_webhook_delivery_duration_ms{quantile="0.99"} 3000
beza_webhook_success_rate 0.993

# Settlement worker
beza_settlement_duration_seconds 45
beza_settlement_merchants_per_minute 120

# Queue
beza_queue_depth{queue="webhook-deliveries"} 45
beza_queue_depth{queue="settlement-retry"} 3
beza_queue_age_seconds{queue="webhook-deliveries"} 2.5
```

## Grafana Dashboard: Merchant Overview

### Row 1: Key Figures
```
┌─────────────┬──────────────┬──────────────┬──────────────┐
│ Active      │ Daily TPV    │ MDR Revenue  │ Avg MDR Rate │
│ Merchants   │              │ (Today)      │              │
│ 3,250       │ 28M SYP      │ 475K SYP     │ 1.7%         │
└─────────────┴──────────────┴──────────────┴──────────────┘
```

### Row 2: Merchant Growth
```
[Line Chart: Cumulative merchant registrations by tier]
X: Time (daily)
Y: Count
Series: Micro, Small, Mid, Enterprise
```

### Row 3: TPV by Payment Method
```
[Stacked Area Chart: Transaction volume by method]
X: Time (hourly, last 7 days)
Y: Volume (SYP)
Series: QR, POS, Payment Link, Web Checkout
```

### Row 4: Settlement Summary
```
[Table: Recent settlements]
Columns: Merchant, Period, Gross, MDR, Net, Status, Time

[Bar Chart: Settlement success/failure by day]
X: Date
Y: Count
Color: Green (success), Red (failed)
```

### Row 5: Webhook Health
```
[Gauge: Webhook delivery success rate]
Threshold: green > 99%, yellow 95-99%, red < 95%

[Table: Merchants with highest webhook failure rate]
Columns: Merchant, URL, Events, Success Rate, Last Attempt
```

### Row 6: QR & Link Performance
```
[Stat: QR scans today]  [Stat: QR payments today]  [Stat: Conversion rate]
1,250                     45                         3.6%

[Stat: Links created]   [Stat: Links paid]          [Stat: Link conversion]
85                        12                         14.1%
```

## Alert Rules (Prometheus)
```yaml
groups:
  - name: merchant_alerts
    rules:
      - alert: QrGenerationHighLatency
        expr: beza_api_duration_ms{endpoint="/merchant/qr/generate", quantile="0.99"} > 2000
        for: 5m
        annotations:
          summary: "QR generation P99 latency > 2s"
          action: "Check QR generator service CPU and CDN health"

      - alert: WebhookDeliveryRateDrop
        expr: beza_webhook_success_rate < 0.95
        for: 5m
        annotations:
          summary: "Webhook delivery rate below 95%"
          action: "Check webhook deliverer service, investigate failed deliveries"

      - alert: SettlementFailure
        expr: beza_settlement_failed_total > 0
        for: 1m
        annotations:
          summary: "Settlement failure detected"
          action: "Check settlement worker logs and CFE service health"

      - alert: MerchantRegistrationSpike
        expr: rate(beza_merchants_total[15m]) > rate(beza_merchants_total[1h]) * 5
        for: 5m
        annotations:
          summary: "Unusual spike in merchant registrations"
          action: "Check for automated/bot registrations, verify fraud rules"

      - alert: PosTerminalOffline
        expr: beza_pos_offline_count > 50
        for: 1h
        annotations:
          summary: "50+ POS terminals offline for > 1 hour"
          action: "Check POS sync service, investigate network issues"

      - alert: PaymentLinkConversionDrop
        expr: rate(beza_link_paid_total[1h]) / rate(beza_link_created_total[1h]) < 0.05
        for: 2h
        annotations:
          summary: "Payment link conversion rate below 5%"
          action: "Check payment page availability, investigate drop-off"
```
