# Monitoring & Observability

## Logging Framework

| Component | Tool | Log Level |
|-----------|------|-----------|
| Backend services | Winston → Elasticsearch | INFO (production), DEBUG (development) |
| Queue workers | BullMQ events → Elasticsearch | INFO |
| API Gateway | Request/response logging | INFO (without body for PII) |
| Agent app | Client-side → remote logging | WARN and above only |
| Sanctions screening | Dedicated audit logger | INFO (all matches logged) |

## Key Metrics (Prometheus)

### Business Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `humanitarian_programs_total` | Gauge | Total active programs |
| `humanitarian_beneficiaries_total` | Gauge | Total enrolled beneficiaries by status |
| `humanitarian_distributions_total` | Counter | Total distributions triggered |
| `humanitarian_distribution_amount_total` | Counter | USD amount distributed |
| `humanitarian_distribution_duration_seconds` | Histogram | Distribution batch processing time |
| `humanitarian_vouchers_issued_total` | Counter | Total vouchers issued |
| `humanitarian_vouchers_redeemed_total` | Counter | Total vouchers redeemed |
| `humanitarian_voucher_value_redeemed_total` | Counter | USD value of voucher redemptions |
| `humanitarian_spending_per_category` | Gauge | Spending by category (food, rent, etc.) |
| `humanitarian_burn_rate_7d` | Gauge | 7-day burn rate per program |
| `humanitarian_sanctions_cleared_total` | Counter | Beneficiaries cleared by sanctions |
| `humanitarian_sanctions_blocked_total` | Counter | Beneficiaries blocked by sanctions |

### Technical Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `humanitarian_api_request_duration_ms` | Histogram | API endpoint response time |
| `humanitarian_api_errors_total` | Counter | API errors by status code |
| `humanitarian_queue_wait_time_seconds` | Histogram | BullMQ job queue wait time |
| `humanitarian_queue_job_duration_seconds` | Histogram | BullMQ job processing time |
| `humanitarian_queue_failed_jobs_total` | Counter | Failed jobs by queue name |
| `humanitarian_biometric_verification_duration_ms` | Histogram | Biometric match time |
| `humanitarian_offline_sync_queue_size` | Gauge | Agent offline queue size |
| `humanitarian_db_query_duration_ms` | Histogram | Database query execution time |

## Dashboards (Grafana)

### Operational Dashboard
- Active programs & budget utilisation
- Distribution throughput (tx/min)
- Queue depth for critical jobs
- API error rate (p95, p99)
- Sanctions screening queue
- Biometric verification success rate

### Business Dashboard
- Total beneficiaries reached (by governorate)
- Spending category breakdown (pie chart)
- Burn rate timeline (7d / 14d / 30d)
- Voucher redemption rate
- Donor report generation status

### Compliance Dashboard
- Sanctions screening results (cleared / pending / blocked)
- Manual review queue
- Duplicate beneficiary alerts
- Audit log integrity check (hash chain verification)

## Alerting Rules

| Alert | Condition | Severity | Channel |
|-------|-----------|----------|---------|
| Distribution failure rate high | `> 2%` of distribution items failed | Critical | PagerDuty + SMS |
| Distribution processing time exceeds threshold | `> 5 minutes` for 10k batch | Critical | PagerDuty |
| Sanctions match pending review > 24h | `> 50` beneficiaries awaiting review | High | Slack + Email |
| Queue backlog (critical) | `> 1000` jobs in critical queue | Critical | PagerDuty |
| API error rate spike | `> 5%` 5xx errors in 5 minutes | High | PagerDuty |
| Offline agent sync queue large | Any agent with `> 500` unsynced verifications | Medium | Slack (agent manager) |
| Biometric verification failure rate | `> 20%` failure rate in 1 hour | Medium | Slack |
| Budget utilisation near limit | `> 90%` of program budget used | Low | Email (program manager) |

## Tracing (OpenTelemetry)

| Span | Parent | Key Attributes |
|------|--------|----------------|
| `POST /distribute` | HTTP request | program_id, batch_id, amount |
| `distribution.process-batch` | Queue job | batch_id, total_beneficiaries |
| `wallet.batch-credit` | distribution.process-batch | count, total_amount, success_count |
| `voucher.redeem` | HTTP request | voucher_code, merchant_id |
| `sanctions.screen` | Queue job | beneficiary_id, lists_checked, match_score |

## Incident Response

| Severity | Response Time | Example |
|----------|---------------|---------|
| Critical (S0) | 15 min | Distribution failing, funds at risk |
| High (S1) | 1 hour | Sanctions screening downtime, API degradation |
| Medium (S2) | 4 hours | Report generation slow, agent sync delayed |
| Low (S3) | 24 hours | Minor UI bugs, non-critical feature requests |
