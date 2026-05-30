# المراقبة والقياس — Monitoring & Observability

## Key Metrics (Prometheus)

### Application Metrics
```prometheus
# Total applications submitted
financing_applications_total{product_type="qard_hasan|murabaha|micro", status="submitted|approved|rejected"}

# Application processing time (seconds)
financing_application_processing_seconds{stage="scoring|underwriting|disbursement"}

# Approval rate
financing_approval_rate{product_type="..."}

# Average time to decision
financing_time_to_decision_minutes{product_type="..."}
```

### Credit Score Metrics
```prometheus
# Score distribution
financing_credit_score_distribution{tier="excellent|good|fair|poor|very_poor"}

# Model prediction time
financing_scoring_duration_seconds{model_version="v1.2"}

# Model accuracy (monthly)
financing_model_accuracy{metric="precision|recall|auc_roc"}
```

### Disbursement Metrics
```prometheus
# Total disbursed amount (SYP)
financing_disbursement_amount_total{product_type="..."}

# Disbursement count
financing_disbursement_count_total{method="wallet|merchant|supplier"}

# Disbursement failure rate
financing_disbursement_failure_rate{reason="insufficient_pool|merchant_error|system_error"}
```

### Repayment Metrics
```prometheus
# Payment collection rate (on-time)
financing_on_time_payment_rate{product_type="..."}

# Delinquency by bucket
financing_delinquency_amount{product_type="...", bucket="1-7|8-14|15-30|31-60|61-90|90+"}

# PAR (Portfolio at Risk)
financing_par_ratio{days="30|60|90"}

# Auto-deduction success rate
financing_auto_deduct_success_rate
```

### Collection Metrics
```prometheus
# Collection effectiveness
financing_collection_rate{period="30|60|90"}

# Restructure success rate
financing_restructure_success_rate

# Default rate
financing_default_rate{product_type="..."}
```

## Logging Strategy

### Structured Logging (JSON)
```json
{
  "timestamp": "2026-05-29T10:30:00Z",
  "level": "info|warn|error",
  "service": "financing-service",
  "trace_id": "uuid",
  "user_id": 12345,
  "action": "application.submitted",
  "metadata": {
    "application_id": 9876,
    "product_type": "qard_hasan",
    "amount": 300000,
    "processing_time_ms": 245
  }
}
```

### Log Levels by Component
| Component | Default Level | Sensitive Events |
|-----------|---------------|------------------|
| Application Service | INFO | SUBMITTED, APPROVED, REJECTED always INFO+ |
| Scoring Service | INFO | Score calculation logged at DEBUG, score returned at INFO |
| Disbursement Service | WARN | DISBURSED at INFO, failures at ERROR |
| Repayment Service | INFO | Auto-deduct success at INFO, failures at WARN |
| Collection Service | WARN | Escalations at WARN, default at ERROR |

## Alerting Rules

### Critical Alerts (PagerDuty)
| Rule | Threshold | Window |
|------|-----------|--------|
| Auto-deduction success rate < 95% | < 95% | 5 minutes |
| Application error rate > 5% | > 5% | 5 minutes |
| Scoring service down | 100% errors | 1 minute |
| Disbursement failure rate > 10% | > 10% | 5 minutes |
| Queue depth > 1000 (any queue) | > 1000 | 1 minute |

### Warning Alerts (Slack)
| Rule | Threshold | Window |
|------|-----------|--------|
| Application volume anomaly > 3x stddev | > 3 sigma | 1 hour |
| NPL ratio increasing week-over-week | > 0.5% increase | 1 week |
| Average scoring time > 10s | > 10s | 5 minutes |
| New fraud pattern detected | any | immediate |

## Dashboards (Grafana)

### Dashboard 1: Financing Operations
```
Row 1: Applications (submitted, approved, rejected) — time series
Row 2: Disbursement volume (SYP) by product type — stacked area
Row 3: Portfolio at Risk (PAR 30, PAR 60, PAR 90) — gauges
Row 4: Collection rate by bucket — bar chart
Row 5: Active users, average loan size, avg credit score — stat panels
```

### Dashboard 2: Scoring & Risk
```
Row 1: Score distribution histogram
Row 2: Model AUC-ROC over time
Row 3: Feature importance (top 10)
Row 4: Default prediction vs actual (confusion matrix)
Row 5: Application approval rate by score tier
```

### Dashboard 3: Financial Health
```
Row 1: Total portfolio (SYP), total disbursed (SYP), total collected (SYP)
Row 2: NPL ratio, Provision coverage ratio, CAR
Row 3: Cash flow forecast (next 30/60/90 days)
Row 4: Charity account balance, charity disbursed this quarter
```

## Runbook Links
| Incident | Runbook |
|----------|---------|
| Auto-deduction failure spike | `/runbooks/financing/auto-deduct-failure.md` |
| Scoring model drift | `/runbooks/financing/model-drift.md` |
| Disbursement queue backlog | `/runbooks/financing/disbursement-backlog.md` |
| Fraud alert triggered | `/runbooks/financing/fraud-alert.md` |
| CBS reporting failure | `/runbooks/financing/cbs-report-failure.md` |
