# 29 — Operations Runbook

## 29.1 Daily Operations Checklist

| Time | Task | Owner | Tool |
|---|---|---|---|
| 08:00 | Check overnight failed payments (retry queue) | Ops Engineer | Grafana + Dashboard |
| 08:30 | Review pending school onboarding applications | Ops Specialist | Admin Dashboard |
| 09:00 | Verify settlement batch from previous night | Treasury Ops | Settlement Report |
| 10:00 | Check WhatsApp template quality (approvals, blocks) | Comms Ops | WhatsApp Manager |
| 12:00 | Monitor midday peak traffic | Ops Engineer | Grafana |
| 15:00 | Process refund requests (24h SLA) | Ops Specialist | Refund Queue |
| 17:00 | Verify end-of-day reconciliation | Treasury Ops | Reconciliation Report |
| 22:00 | Pre-check batch jobs (invoice gen, settlement) | Ops Engineer | Cron Job Dashboard |

## 29.2 Weekly Operations

| Day | Task | Owner |
|---|---|---|
| Monday | Export weekly collection report for schools that opted in | Ops |
| Tuesday | Audit 10 random schools (compliance check) | Compliance |
| Wednesday | Review AI model performance (last week's predictions vs actuals) | Data Science |
| Thursday | Onboard batch of new schools (sales handover) | Ops + Sales |
| Friday | Limited ops (automated only; no manual interventions except critical) | On-call |
| Saturday | Process financing applications that require manual review | Credit Ops |

## 29.3 Monthly Operations

| Week | Task | Owner |
|---|---|---|
| W1 | Generate monthly financial report for each school | Treasury |
| W1 | Submit regulatory reports to CBS, Tax Authority | Compliance |
| W2 | School tier review (upgrade/downgrade based on usage) | Ops + Sales |
| W3 | Retrain AI models with month's data | Data Science |
| W4 | Billing: generate invoices for Starter/Pro/Enterprise schools | Finance |
| W4 | Platform-wide reconciliation audit | Internal Audit |

## 29.4 Incident Response Runbook

### Incident Types

| Type | Example | Initial Response |
|---|---|---|
| **P0 — Critical** | Payment processing down; no parent can pay | 5-min response; full team |
| **P1 — High** | WhatsApp reminders not sending; school dashboard slow | 15-min response |
| **P2 — Medium** | Export not working; minor UI bug | 2-hour response |
| **P3 — Low** | Translation typo; cosmetic issue | Next sprint |

### P0 Response
1. **Detect**: Alert triggers (pager)/user reports → On-call engineer acknowledges (5 min)
2. **Triage**: Is it payment service, DB, or network? (10 min)
3. **Mitigate**: Failover, rollback, or feature flag disabled (target 30 min)
4. **Resolve**: Root cause fix deployed (4 hours)
5. **Post-mortem**: Within 24 hours — timeline, root cause, action items

### Rollback Procedure
```bash
# If current deployment is broken:
kubectl rollout undo deployment/education-api -n education
kubectl rollout status deployment/education-api -n education

# If DB migration needs rollback:
# Run down-migration for specific version
npm run migrate:down -- --version <previous-version>

# Verify
curl -f https://api.beza.sy/education/v1/health
```

## 29.5 Database Operations

| Operation | Command/Procedure | Notes |
|---|---|---|
| Manual backup | `pg_dump -Fc edu_db > /backups/edu_$(date +%Y%m%d).dump` | Daily cron already configured |
| Restore from backup | `pg_restore -d edu_db /backups/edu_20260528.dump` | Requires DB down |
| Rebuild index | `REINDEX DATABASE edu_db;` | Low-traffic window (02:00) |
| Vacuum analyse | `VACUUM ANALYZE;` | Automated (auto-vacuum on) |
| Query performance | `EXPLAIN ANALYZE` on slow queries | Logged on any query > 1s |
| Connection pool reset | `SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE ...` | Kill stuck connections |

## 29.6 Common Troubleshooting

### Payment Failed — Insufficient Balance
1. Check parent wallet balance in Payment Core
2. Check if there are pending holds (double-check idempotency)
3. Advise parent to top up wallet via card/bank transfer
4. If balance correct but still failing → escalate to Payment Core team

### Settlement Batch Stuck
1. Check settlement service logs: `kubectl logs -n education settlement-service -f`
2. Verify bank API is reachable: `curl -f https://bank-api.sy/health`
3. Check if file (SFTP) was delivered → check SFTP outbox
4. If stuck > 2 hours → manual trigger: `POST /admin/settlements/retry`

### School Dashboard Shows Wrong Data
1. Clear Redis cache: `redis-cli DEL edu:dashboard:{school_id}`
2. Check if invoice generation completed successfully
3. Verify timezone: Syrian time (UTC+3) — all stored in UTC, displayed in +3
4. Check for stale browser cache → instruct school to hard refresh (Ctrl+F5)

### WhatsApp Messages Not Delivering
1. Check WhatsApp Business API account health dashboard
2. Verify template is approved and not blocked
3. Check recipient's opt-out status
4. If number invalid → fallback to SMS should have triggered
5. If SMS also failed → mark as undeliverable in DB

## 29.7 Key Contacts

| Team | Role | Contact |
|---|---|---|
| Education Service Owner | Product Lead | TBD |
| Payment Core | Tech Lead | TBD |
| WhatsApp API | Comms Ops | TBD |
| School Support | Customer Success | TBD |
| CBS Compliance | Legal & Compliance | TBD |
| Infrastructure | DevOps Lead | TBD |

## 29.8 Environment Details

| Environment | URL | DB | Access |
|---|---|---|---|
| Production | `api.beza.sy/education/v1` | edu-db-prod | Ops + On-call only |
| Staging | `staging-api.beza.sy/education/v1` | edu-db-staging | Engineering team |
| Sandbox | `sandbox-api.beza.sy/education/v1` | edu-db-sandbox | External devs |
| Development | Localhost/DevCloud | dev DB per dev | Developers |
