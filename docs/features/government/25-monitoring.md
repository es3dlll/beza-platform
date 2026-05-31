# Government Collections Monitoring

## Key Metrics (Prometheus)

### Business Metrics
```prometheus
# GOVERNMENT TRANSACTION METRICS

# Transaction volume by service
government_transactions_total{service_type="tax_income", status="completed"}
government_transactions_total{service_type="tax_property", status="completed"}
government_transactions_total{service_type="passport", status="completed"}
government_transactions_total{service_type="tuition", status="completed"}
government_transactions_total{service_type="traffic_fine", status="completed"}
government_transactions_total{service_type="vehicle_registration", status="completed"}
government_transactions_total{service_type="court_fee", status="completed"}
government_transactions_total{service_type="municipality_fee", status="completed"}
government_transactions_total{service_type="civil_registry", status="completed"}

# Revenue
government_revenue_total{type="fee"}           # Beza fee income
government_revenue_total{type="penalty"}        # Late payment penalties
government_revenue_total{type="discount"}       # Early payment discounts (negative revenue)

# Volume by ministry
government_volume_by_biller{biller_code="MOF"}  # Ministry of Finance
government_volume_by_biller{biller_code="MOI"}  # Ministry of Interior
government_volume_by_biller{biller_code="TRAF"} # Traffic Directorate

# Users
government_unique_payers_total
government_saved_payers_total
government_guest_payments_total
```

### Technical Metrics
```prometheus
# MINISTRY INTEGRATION HEALTH
ministry_api_latency_seconds{biller="MOF", endpoint="query"}
ministry_api_latency_seconds{biller="MOI", endpoint="confirm"}
ministry_api_error_total{biller="TRAF", error_type="timeout"}
ministry_api_error_total{biller="COURT", error_type="auth_failure"}
ministry_api_up{adapter="MinistryOfFinanceAdapter"}   # 1 = up, 0 = down

# SETTLEMENT METRICS
settlement_lag_hours{biller="MOF"}            # Hours since last successful settlement
settlement_amount_total{biller="MOF"}
settlement_failure_total{biller="MOI"}
settlement_pending_count{biller="TRAF"}

# RECONCILIATION
reconciliation_variance_amount{biller="MOF"}  # Beza - Ministry in SYP
reconciliation_mismatch_count{biller="MOF"}
reconciliation_unreconciled_days{biller="TRAF"}

# PERFORMANCE
payment_p95_duration_seconds
payment_p99_duration_seconds
ministry_query_p95_seconds
ministry_query_p99_seconds
receipt_generation_p95_seconds
```

### Alerts
| Alert | Condition | Severity | Response |
|-------|-----------|----------|----------|
| MinistryDown | ministry_api_up = 0 for >5 min | Critical | Notify ministry contact, switch to queuing mode |
| SettlementLagBreached | settlement_lag_hours > 48 | Critical | Escalate to finance, manual wire transfer |
| ReconciliationVarianceHigh | reconciliation_variance_amount > 100,000 | Critical | Block settlement, investigate, notify finance |
| HighPaymentFailureRate | failure_rate > 5% in 1 hour | Warning | Investigate ministry API, check rate limits |
| ReceiptGenerationSlow | receipt_generation_p95 > 5s | Warning | Scale receipt workers, check storage |
| DuplicatePaymentAttempt | idempotency_collision_count > 10/hour | Warning | Investigate client retry logic |
| LowMinistryBalance | settlement_reserve < 10M SYP | Warning | Notify treasury, top up settlement account |

## Dashboards (Grafana)

### Dashboard 1: Government Payments Overview
```
Row 1: [Revenue Gauge] [Transaction Volume Sparkline] [Avg Payment Time]
Row 2: ┌─────────────────────────────┐ ┌─────────────────────────────┐
        │ Transactions by Service     │ │ Revenue by Service          │
        │ (Stacked area, 7d)          │ │ (Bar chart, 30d)            │
        └─────────────────────────────┘ └─────────────────────────────┘
Row 3: ┌─────────────────────────────┐ ┌─────────────────────────────┐
        │ Ministry API Health         │ │ Settlement Status           │
        │ (Status grid: up/down/lag)  │ │ (Table: biller, last settle, │
        └─────────────────────────────┘ │ amount, lag)               │
                                         └─────────────────────────────┘
Row 4: [Reconciliation Variance Table] [Recent Failed Payments List]
```

### Dashboard 2: Ministry Integration Health
```
Row 1: [Ministry API Latency Heatmap]
Row 2: ┌─────────────────────────────┐ ┌─────────────────────────────┐
        │ Error Rate by Ministry     │ │ Rate Limit Usage            │
        │ (per-minute, stacked)      │ │ (current / limit per min)   │
        └─────────────────────────────┘ └─────────────────────────────┘
Row 3: [SFTP File Transfer Status] [Queue Depth for Each Ministry]
```

## Logging

### Structured Logging (JSON)
```json
{
  "timestamp": "2025-08-15T10:23:45Z",
  "level": "info",
  "service": "government-collect",
  "transaction": "gov_txn_abc123",
  "action": "payment.completed",
  "biller": "MOF",
  "service_type": "tax_income",
  "amount": 262500,
  "duration_ms": 2345,
  "ministry_latency_ms": 1200,
  "receipt_ref": "GOV-2025-0815-7823",
  "user_id": 42,
  "ip_address": "185.xx.xx.xx",
  "correlation_id": "cid_abc123"
}
```

### Log Retention
| Log Type | Retention | Storage |
|----------|-----------|---------|
| Application logs | 90 days | Elasticsearch |
| Ministry API call logs | 1 year | Cold storage (S3 Glacier) |
| Audit logs | 7 years | Append-only DB table |
| Payment debug logs | 30 days | Elasticsearch |
| Receipt access logs | 1 year | CloudWatch / DataDog |
