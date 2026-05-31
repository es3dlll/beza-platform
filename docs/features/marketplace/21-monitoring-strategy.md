# Monitoring & Observability

## Key Metrics (Dashboards)

### Business KPIs
| Metric | Source | Refresh |
|---|---|---|
| GMV (daily/weekly/monthly) | Orders DB | Real-time |
| Order volume | Orders DB | Real-time |
| Top-up volume | Topups DB | Real-time |
| Commission earned | Commissions DB | Real-time |
| Vendor payouts | Payouts DB | Daily |
| Active buyers (DAU/MAU) | Analytics | Daily |
| Gift cards purchased | Gift cards DB | Real-time |

### Technical KPIs
| Metric | Source | Alert Threshold |
|---|---|---|
| API latency (p50/p95/p99) | Prometheus | p95 > 1s |
| Error rate (by endpoint) | Prometheus | > 1% |
| Top-up success rate | App logs | < 99% |
| Fulfillment success rate | App logs | < 99.5% |
| Telecom API latency | App metrics | p95 > 3s |
| Database connections | PG exporter | > 80% pool |
| Redis cache hit rate | Redis exporter | < 80% |
| Queue depth (fulfillment) | RabbitMQ | > 100 |

## Alerting Rules

### P1 — Critical (SMS + Phone)
- Top-up failure rate > 5% in 5 minutes
- Telecom API completely unreachable
- Order creation failure rate > 10%
- Database replication lag > 30s
- Payment hold/release mismatch

### P2 — High (SMS)
- API latency p95 > 2s for 5 minutes
- Vendor payout processing failure
- Gift card generation failure rate > 2%
- Promo code system errors
- Search index out of sync

### P3 — Medium (Slack/Email)
- Low stock alerts for top-selling products
- Vendor payout requests > 48h old
- Commission reconciliation difference > 1%
- Slow admin queries (> 5s)

## Logging

| Log Type | Retention | Sample Rate |
|---|---|---|
| API access logs | 30 days | 100% |
| Order lifecycle events | 90 days | 100% |
| Telecom API requests/responses | 90 days | 100% (no PII) |
| Error logs | 180 days | 100% |
| Debug logs | 7 days | 1% (sampled) |
| Audit logs (admin actions) | 1 year | 100% |

## Runbook Links

| Scenario | Runbook |
|---|---|
| Syriatel API outage | Runbooks/syriatel-outage.md |
| MTN API degradation | Runbooks/mtn-degradation.md |
| Wallet hold/release failure | Runbooks/wallet-hold-failure.md |
| Vendor payout stuck | Runbooks/payout-stuck.md |
| Gift card generation failure | Runbooks/giftcard-failure.md |
