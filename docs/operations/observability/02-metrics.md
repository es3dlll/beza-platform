# Metrics Standard

> Single source of truth for metrics naming, collection, and dashboards across ALL Beza Platform features.

## Prometheus Metrics

### Naming Convention
```
beza_{domain}_{entity}_{metric}[_{unit}]
beza_wallet_transfer_duration_seconds
beza_auth_login_attempts_total
beza_notification_push_delivered_total
```

### Rules
1. **Prefix**: `beza_`
2. **Domain**: `wallet`, `auth`, `fx`, `remittance`, `notification`, `settlement`, `compliance`, `agent`, `merchant`
3. **Entity**: `transfer`, `login`, `kyc`, `sms`, `push`, `rate`
4. **Metric**: `duration`, `count`, `total`, `errors`, `active`
5. **Unit**: `seconds`, `bytes`, `total` (for counters)
6. **Separator**: Underscores only (no dots, no hyphens)
7. **Suffix**: `_total` for counters, `_seconds` for durations, `_bytes` for sizes
8. **Labels**: Keep cardinality low (< 100 label values per metric)

## RED Method (Rate / Errors / Duration)

Every service MUST expose these three metrics:

### Rate
```prometheus
# Request rate
beza_{service}_requests_total{operation="transfer.create", status="success"} 1234
beza_{service}_requests_total{operation="transfer.create", status="failure"} 56

# Throughput
rate(beza_wallet_requests_total[5m])
```

### Errors
```prometheus
# Error rate
beza_{service}_errors_total{operation="transfer.create", error_code="WAL_001"} 23

# Error ratio
rate(beza_wallet_errors_total[5m]) / rate(beza_wallet_requests_total[5m])
```

### Duration
```prometheus
# Request duration histogram
beza_{service}_request_duration_seconds_bucket{operation="transfer.create", le="0.1"} 100
beza_{service}_request_duration_seconds_bucket{operation="transfer.create", le="0.25"} 300
beza_{service}_request_duration_seconds_bucket{operation="transfer.create", le="0.5"} 500
beza_{service}_request_duration_seconds_bucket{operation="transfer.create", le="1.0"} 800
beza_{service}_request_duration_seconds_bucket{operation="transfer.create", le="2.5"} 950
beza_{service}_request_duration_seconds_bucket{operation="transfer.create", le="5.0"} 990
beza_{service}_request_duration_seconds_bucket{operation="transfer.create", le="+Inf"} 1000
beza_{service}_request_duration_seconds_sum{operation="transfer.create"} 450.5
beza_{service}_request_duration_seconds_count{operation="transfer.create"} 1000

# Latency percentiles
histogram_quantile(0.95, rate(beza_wallet_request_duration_seconds_bucket[5m]))
```

## Standard Metrics per Domain

### Wallet Service
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `beza_wallet_balance_total` | Gauge | `wallet_type`, `currency` | Total balance across all wallets |
| `beza_wallet_transfer_total` | Counter | `status`, `currency` | Transfer operations |
| `beza_wallet_transfer_amount_total` | Counter | `currency` | Total amount transferred |
| `beza_wallet_transfer_duration_seconds` | Histogram | — | Transfer processing time |
| `beza_wallet_daily_limit_exceeded_total` | Counter | — | Daily limit exceeded events |
| `beza_wallet_active_wallets` | Gauge | `kyc_level` | Active wallet count |

### Auth Service
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `beza_auth_login_attempts_total` | Counter | `method`, `result` | Login attempts |
| `beza_auth_login_duration_seconds` | Histogram | — | Login processing time |
| `beza_auth_mfa_challenges_total` | Counter | `method`, `result` | MFA challenges |
| `beza_auth_token_refresh_total` | Counter | `result` | Token refresh operations |
| `beza_auth_active_sessions` | Gauge | `role` | Current active sessions |
| `beza_auth_device_registrations_total` | Counter | `platform` | New device registrations |

### FX Service
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `beza_fx_rate_requests_total` | Counter | `from_currency`, `to_currency`, `provider` | Rate fetch requests |
| `beza_fx_rate_duration_seconds` | Histogram | `provider` | Rate fetch latency |
| `beza_fx_rate_provider_up` | Gauge | `provider` | Provider health (1=up, 0=down) |
| `beza_fx_rate_lock_created_total` | Counter | `from_currency`, `to_currency` | Rate locks created |
| `beza_fx_rate_lock_expired_total` | Counter | — | Rate lock expiry events |
| `beza_fx_rate_stale` | Gauge | `from_currency`, `to_currency` | Rate age in seconds |

### Notification Service
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `beza_notification_push_sent_total` | Counter | `priority`, `result` | Push notifications sent |
| `beza_notification_sms_sent_total` | Counter | `provider`, `type`, `result` | SMS sent |
| `beza_notification_email_sent_total` | Counter | `template`, `result` | Emails sent |
| `beza_notification_sms_cost_total` | Counter | `provider`, `currency` | SMS cost accumulated |
| `beza_notification_queue_depth` | Gauge | `queue` | RabbitMQ queue depth |
| `beza_notification_dlq_depth` | Gauge | `queue` | Dead letter queue depth |

### Compliance Service
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `beza_compliance_aml_flags_total` | Counter | `rule_name`, `level` | AML rule flags |
| `beza_compliance_sanctions_matches_total` | Counter | `match_level` | Sanctions match events |
| `beza_compliance_kyc_submissions_total` | Counter | `level`, `result` | KYC submissions |
| `beza_compliance_kyc_duration_hours` | Histogram | `level` | KYC processing time |
| `beza_compliance_str_filed_total` | Counter | — | STR filings |
| `beza_compliance_pending_review` | Gauge | — | Items awaiting manual review |

### Agent Service
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `beza_agent_active_agents` | Gauge | `status` | Active agents count |
| `beza_agent_float_total` | Gauge | — | Total agent float balance |
| `beza_agent_cash_in_total` | Counter | — | Cash-in operations |
| `beza_agent_cash_out_total` | Counter | — | Cash-out operations |
| `beza_agent_commission_total` | Counter | — | Commission earned |

### Settlement Service
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `beza_settlement_runs_total` | Counter | `status` | Settlement runs |
| `beza_settlement_duration_seconds` | Histogram | — | Settlement processing time |
| `beza_settlement_amount_total` | Counter | `currency` | Total settled amount |
| `beza_settlement_discrepancies_total` | Counter | — | Reconciliation discrepancies |

## Business Metrics

| Metric | Source | Period | Description |
|--------|--------|--------|-------------|
| Active users (daily) | Auth | 24h | Users with at least one request |
| Active users (monthly) | Auth | 30d | MAU |
| Transaction volume (daily) | Wallet | 24h | Total SYP transferred |
| Transaction count (daily) | Wallet | 24h | Number of transactions |
| Average transaction size | Wallet | 24h | Mean amount per txn |
| Push delivery rate | Notification | 24h | Delivered / Sent |
| SMS delivery rate | Notification | 24h | Delivered / Sent |
| KYC conversion | Compliance | 30d | L0→L1, L1→L2 rates |
| Agent float utilization | Agent | 24h | Avg float used / float limit |
| AML false positive rate | Compliance | 30d | False flags / Total flags |
| System Uptime | Infra | 30d | 99.9% target |

## Grafana Dashboard Standards

### Dashboard Naming
`[Domain] - [Dashboard Name]`
Examples: `Wallet - Overview`, `Auth - Performance`, `Compliance - AML Dashboard`

### Required Dashboard Sections
1. **RED Metrics** — Rate, Errors, Duration (top row, always visible)
2. **Service Health** — Uptime, error rate, latency p50/p95/p99
3. **Business KPIs** — Domain-specific business metrics
4. **Historical Comparison** — Week-over-week, month-over-month
5. **Top-N Errors** — Most frequent error codes
6. **Alerts** — Active alert count + recent alert history

### Dashboard Variables
| Variable | Description | Default |
|----------|-------------|---------|
| `$environment` | prod/staging/dev | production |
| `$service` | Service name | all |
| `$tenant` | Tenant filter | all |
| `$timeRange` | Time window | Last 24h |

### Dashboard Refresh
| Time Range | Auto-Refresh |
|------------|-------------|
| Last 15 min | 30s |
| Last 1 hour | 1m |
| Last 6 hours | 5m |
| Last 24 hours | 5m |
| Last 7 days | 15m |
| Last 30 days | 1h |

### Annotation Rules
- Deployments automatically annotated with version tag
- Configuration changes annotated
- Alert state changes annotated
- Manual annotations for incidents (format: `[P0] Brief description`)
