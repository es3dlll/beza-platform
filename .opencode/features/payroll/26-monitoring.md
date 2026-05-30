# 26 — Monitoring & Alerting

---

## Key Metrics

| Metric | Instrument | Target | Alert |
|--------|-----------|--------|-------|
| Batch processing duration | Histogram (Prometheus) | p95 < 5s | > 10s → warning; > 30s → critical |
| Batch success rate | Counter (succeeded / total) | > 99 % | < 95 % → warning; < 90 % → critical |
| Failed transactions | Counter (by reason) | < 1 % | > 5 % → warning |
| CFE hold latency | Histogram | p95 < 500ms | > 2s → critical |
| CFE credit latency | Histogram | p95 < 1s | > 3s → warning |
| CSV validation time | Histogram | p95 < 2s | > 5s → warning |
| Payslip generation time | Histogram | p95 < 1s/employee | > 3s → warning |
| Company balance (low) | Gauge | > 1 month payroll | < 2 weeks → warning; < 0 → critical |
| Settlement overdue | Counter (days past due) | 0 | > 1 day → warning; > 3 days → critical |

## Logging

| Component | Format | Retention | Tool |
|-----------|--------|-----------|------|
| API server | JSON (structured) | 30 days | ELK Stack (Elasticsearch + Kibana) |
| Payroll worker (Celery) | JSON | 30 days | ELK Stack |
| Database (PostgreSQL) | CSV (slow query log) | 7 days | pgBadger |
| CFE integration | JSON (all requests/responses) | 90 days | ELK Stack |
| Audit log | DB table (append-only) | 7 years | PostgreSQL |

## Dashboards (Grafana)

### Dashboard 1: Payroll Operations

```
┌───────────────────────────────────────────────────┐
│  Payroll Operations  │  Last 24 hours             │
├───────────────────┬───────────────────────────────┤
│  Batches: 142      │  Success Rate: 98.7 %        │
│  Employees: 21,300 │  Failed: 276 (1.3 %)         │
│  Total: SYP 2.1B  │  Fees: SYP 10.5M             │
├───────────────────┴───────────────────────────────┤
│  ┌──── Batch Duration ───────────────────────┐   │
│  │  [histogram — p50: 1.2s, p95: 4.1s]       │   │
│  └────────────────────────────────────────────┘   │
│  ┌──── Failed Transactions by Reason ─────────┐   │
│  │  wallet_not_active:   142 (51 %)            │   │
│  │  insufficient_balance: 89 (32 %)            │   │
│  │  user_not_found:      30 (11 %)             │   │
│  │  cfe_error:           15 (6 %)              │   │
│  └────────────────────────────────────────────┘   │
└───────────────────────────────────────────────────┘
```

### Dashboard 2: Company Health

Per-company view:
- Balance vs. payroll trend (30 days)
- Batch success rate (30 days)
- Settlement status
- Failed employees count
- Last batch timestamp

## Alerting Rules (PagerDuty / Opsgenie)

| Alert | Condition | Severity | Channel |
|-------|-----------|----------|---------|
| BatchCriticalFailure | Batch status = `failed` (all employees) | P1 | Phone call + Slack |
| HighFailureRate | Batch failure rate > 20 % | P1 | Phone call |
| CFEUnreachable | CFE client error rate > 10 % in 5 min | P1 | Phone call |
| CFESlow | CFE p95 latency > 5s | P2 | Slack |
| BalanceCritical | Any company balance < 0 (overdrawn) | P1 | Phone + Slack |
| SettlementOverdue | Any settlement > 3 days overdue | P2 | Slack |
| LowDiskSpace | Payslip storage < 10 % free | P2 | Slack |
| RetryExhausted | Employee enters failed_permanent status | P3 | Slack |
