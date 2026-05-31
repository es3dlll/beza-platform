# Alerting Standards

> Single source of truth for alert severity, response times, escalation, and runbooks across ALL Beza Platform features.

## Severity Definitions

| Severity | Label | Description | Response Time | SLA |
|----------|-------|-------------|---------------|-----|
| P0 | Critical | System down, data loss, security breach | 15 min | 2 hours |
| P1 | High | Major feature unavailable, degraded performance | 30 min | 4 hours |
| P2 | Medium | Partial feature impact, non-critical errors | 2 hours | 24 hours |
| P3 | Low | Minor issue, cosmetic, informational | 24 hours | 7 days |

### P0 — Critical
**Definition**: Complete service outage, data corruption, active security breach, or financial loss.

**Examples**:
- Wallet service down (all transfers fail)
- Database unresponsive
- Active security incident (unauthorized access)
- Data corruption or integrity violation
- CFE posting failure causing financial loss
- Settlement run fails causing reconciliation gap
- Multiple failed logins indicating brute force attack

**Response**:
- 15min to acknowledge (on-call)
- 2h to mitigate (rollback, feature flag, hotfix)
- Incident commander assigned
- War room (Slack + Zoom)
- Post-mortem within 48 hours

### P1 — High
**Definition**: Major feature unavailable impacting many users, significant performance degradation.

**Examples**:
- Transfer API returning 500 errors (p95 > 5s)
- Login failures > 5% rate
- FCM/SMS delivery delay > 30 minutes
- Agent cash-in/out unavailable
- KYC verification pipeline down
- Rate provider failure (no fallback)

**Response**:
- 30min to acknowledge
- 4h to mitigate
- Post-mortem within 5 business days

### P2 — Medium
**Definition**: Feature partially unavailable, non-critical errors, minor performance degradation.

**Examples**:
- Email delivery delayed
- Push notification delivery rate < 90%
- Admin UI slow (p95 > 3s)
- Search results stale (< 5min delay)
- Background job retries elevated
- Single currency rate provider down (fallback active)

**Response**:
- 2h to acknowledge
- 24h to fix
- No post-mortem required (tracked in sprint)

### P3 — Low
**Definition**: Cosmetic issues, informational alerts, non-urgent.

**Examples**:
- Dashboard metric missing
- Log warning rate elevated
- Non-critical dependency slow but not failing
- Expiring certificate (> 7 days)
- Test coverage dropped

**Response**:
- 24h to acknowledge
- 7 days to fix
- Tracked in backlog

## Alert Rules

### Infrastructure Alerts
| Rule | Metric | Threshold | Duration | Severity |
|------|--------|-----------|----------|----------|
| CPU high | `node_cpu_seconds_total` | > 80% | 5min | P2 |
| Memory high | `node_memory_Active_bytes` | > 85% | 5min | P2 |
| Disk full | `node_filesystem_avail_bytes` | < 10% | 1min | P1 |
| Disk full (critical) | `node_filesystem_avail_bytes` | < 5% | 1min | P0 |
| OOM killer | `node_vmstat_oom_kill` | > 0 | instant | P0 |
| Pod restarting | `kube_pod_container_status_restarts_total` | > 3 | 5min | P1 |
| Pod crash loop | `kube_pod_container_status_waiting_reason` | CrashLoopBackOff | instant | P0 |

### Application Alerts
| Rule | Metric | Threshold | Duration | Severity |
|------|--------|-----------|----------|----------|
| High error rate | `rate(beza_errors_total[5m])` | > 5% | 5min | P1 |
| High latency p95 | `histogram_quantile(0.95, rate(...))` | > 3s | 5min | P1 |
| High latency p99 | `histogram_quantile(0.99, rate(...))` | > 5s | 5min | P0 |
| Zero throughput | `rate(beza_requests_total[5m])` | == 0 | 2min | P0 |
| Queue growing | `beza_notification_queue_depth` | > 1000 | 5min | P2 |
| DLQ messages | `beza_notification_dlq_depth` | > 10 | 1min | P1 |
| Rate limit hits | `rate(beza_rate_limit_hits_total[5m])` | > 100 | 5min | P2 |

### Business Alerts
| Rule | Metric | Threshold | Duration | Severity |
|------|--------|-----------|----------|----------|
| Transfer failure spike | `beza_wallet_transfer_total{status="failure"}` | > 10% | 5min | P1 |
| Login failure spike | `beza_auth_login_attempts_total{result="failure"}` | > 20% | 5min | P1 |
| AML flag spike | `rate(beza_compliance_aml_flags_total[1h])` | > 5x normal | — | P1 |
| Sanctions match | `beza_compliance_sanctions_matches_total{match_level="exact"}` | > 0 | instant | P0 |
| Settlement failure | `beza_settlement_runs_total{status="failure"}` | > 0 | instant | P0 |
| KYC queue > threshold | `beza_compliance_pending_review` | > 100 | 30min | P2 |
| SMS cost spike | `rate(beza_notification_sms_cost_total[24h])` | > 2x normal | — | P2 |

## Escalation Matrix

### On-Call Rotation
| Role | Coverage | Channels |
|------|----------|----------|
| Primary on-call | 24/7 | PagerDuty push + SMS |
| Secondary on-call | 24/7 (backup) | PagerDuty push |
| Engineering lead | Business hours | Slack + Phone |
| CTO | 24/7 (P0 only) | Phone |

### Escalation Path
```
P0 Alert → PagerDuty notifies Primary (15min)
  → If unacknowledged after 5min → Secondary (10min)
    → If unacknowledged after 5min → Engineering Lead (5min)
      → If unacknowledged → CTO

Non-P0 Alert → PagerDuty notifies Primary
  → If unacknowledged after SLA/2 → Secondary
    → If unacknowledged after SLA → Engineering Lead
```

### Escalation Contacts
| Role | Contact Method | P0 | P1 | P2 | P3 |
|------|---------------|:--:|:--:|:--:|:--:|
| Primary on-call | PagerDuty push | ✓ | ✓ | ✓ | — |
| Secondary on-call | PagerDuty push | ✓ | ✓ | ✓ | — |
| Engineering lead | Slack + Phone | ✓ | ✓ | — | — |
| Compliance officer | Phone | ✓ | — | — | — |
| CTO | Phone | ✓ | — | — | — |
| CEO | Phone | ✓ (financial loss) | — | — | — |

## Runbook Standard Format

Every alert MUST have an associated runbook stored in the repository.

### Runbook Template
```markdown
# Runbook: [Alert Name]

## Severity: P[0-3]

## Description
[What triggers this alert, what it means]

## Impact
[What users/services are affected]

## Pre-requisites
- [Access needed: AWS console, K8s cluster, DB read-only]
- [Credentials location]
- [Communication channel: #alerts-wallet]

## Immediate Steps (First 5 minutes)
1. **Acknowledge** alert in PagerDuty
2. **Check** the dashboard: [Grafana link]
3. **Verify** service status: `kubectl get pods -n beza`
4. **Check** logs: `kubectl logs -n beza deployment/wallet-service --tail=100`
5. **Notify** #incidents Slack channel

## Diagnosis Steps
### Check A
```bash
# Command to run
kubectl exec -n beza deploy/wallet-service -- php artisan check:health
```

### Check B
```sql
-- Database query if needed
SELECT COUNT(*) FROM transactions WHERE created_at > NOW() - INTERVAL '5 minutes';
```

## Resolution Steps
### Rollback
```bash
kubectl rollout undo deployment/wallet-service -n beza
```

### Feature Flag
```bash
# Disable feature
kubectl exec -n beza deploy/wallet-service -- php artisan feature:disable transfer
```

### Hotfix
1. Create branch `hotfix/description`
2. Fix code
3. Deploy: `kubectl set image deployment/wallet-service -n beza wallet-service=beza/wallet-service:NEW_TAG`
4. Verify fix on staging before prod

## Verification
- [ ] Error rate back to normal (< 1%)
- [ ] Latency p95 < 2s
- [ ] All health checks pass
- [ ] Monitoring dashboard stable for 10 minutes

## Post-Mortem
- [ ] Root cause identified
- [ ] Fix applied to all environments
- [ ] Monitoring improved (if applicable)
- [ ] Runbook updated
- [ ] Post-mortem document created

## Related
- [Link to Grafana dashboard]
- [Link to Sentry errors]
- [Link to related PRs]
```

### Runbook Index
| Alert | Runbook Path |
|-------|-------------|
| Wallet service down | `runbooks/p0/wallet-down.md` |
| High transfer error rate | `runbooks/p1/transfer-errors.md` |
| Database unresponsive | `runbooks/p0/database-down.md` |
| KYC pipeline failure | `runbooks/p1/kyc-pipeline-down.md` |
| SMS delivery failure | `runbooks/p2/sms-delivery.md` |
| Settlement failure | `runbooks/p0/settlement-failure.md` |
| AML sanctions match | `runbooks/p0/sanctions-match.md` |

## Silence Rules
| Alert | Silence Duration | Reason |
|-------|-----------------|--------|
| CPU > 80% | 1h | Known batch job runs hourly |
| Memory > 85% | 30min | During deployments |
| Queue depth > 1000 | 15min | Burst traffic expected during campaigns |
| SMS cost spike | 24h | During promotional campaigns (pre-approved) |

### Silence Policy
- Silences must have a Jira ticket reference
- Silences auto-expire (no indefinite silences)
- Compliance alerts can only be silenced by compliance team
- P0 alerts cannot be silenced (except during maintenance window)
