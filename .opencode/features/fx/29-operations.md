# FX Engine Operations

## Operational Workflows

### User Support Scenarios

#### Scenario 1: "The rate I saw was different from what I got"
```
1. User contacts support via in-app chat
2. Agent asks for conversion reference or timestamp
3. Look up the conversion:
   - Check fx_conversions for rate_used vs shown rate
   - Check rate lock record: was the rate locked?
4. If rate_used ≠ locked rate:
   → Rare bug — escalate to engineering
   → Refund difference if error is on Beza side
5. If rate locked at time T but rate changed by time of conversion:
   → Explain: "السعر يضمن فقط طوال فترة التثبيت (30 ثانية)"
   → If locked rate expired: "انتهت صلاحية التثبيت، تم استخدام السعر الجديد"
6. If user misunderstood spread:
   → Show: mid rate vs Beza rate breakdown
   → Explain: "السعر يشمل هامش Beza بالإضافة إلى سعر السوق"
```

#### Scenario 2: "My conversion failed but money is gone"
```
1. Check conversion status:
   SELECT * FROM fx_conversions WHERE reference = 'FX-CONV-...';
2. If status = 'failed':
   - Check CFE: was hold placed?
   - If hold placed: "تم إلغاء الحجز، سيعود المبلغ خلال دقائق"
   - If hold not placed: "لم يتم خصم المبلغ من محفظتك"
3. If status = 'pending':
   - Check CFE posting queue
   - Manual retry via admin panel
   - If retry fails: reverse and refund
4. If user balance decreased but conversion failed:
   → Check for concurrent transaction on same wallet
   → Manual adjustment required (finance team approval)
```

#### Scenario 3: "Rate lock disappeared before 30 seconds"
```
1. Check lock record:
   SELECT * FROM fx_rate_locks WHERE lock_id = '...';
2. If lock was active but user claims early expiry:
   - Check for admin rate override during lock window
   - Override causes all active locks to expire
   - If override occurred: explain to user, offer new lock
3. Check user's other active locks:
   - User can only have 1 active lock at a time
   - If another conversion was in progress: explain conflict
4. If no valid reason found:
   → Investigate Redis TTL issue
   → Check for clock skew between app server and Redis
```

### Daily Operations Checklist
```
FX Operations Daily Checklist:

☐ 07:00 — Check all rate providers health (Grafana)
☐ 07:30 — Verify first CBS rate fetch of the day succeeded
☐ 08:00 — Generate CBS daily rate report → submit to Central Bank
☐ 08:30 — Review rate anomaly alerts from overnight
☐ 09:00 — Check conversion success rate (target > 99.5%)
☐ 09:30 — Monitor rate lock usage rate (target > 60%)
☐ 10:00 — Review provider response times (degradation check)
☐ 11:00 — Check provider circuit breakers (any open?)
☐ 12:00 — Mid-day review: conversion volume vs daily projection
☐ 14:00 — Check provider SLA compliance (uptime, response time)
☐ 15:00 — Market close at Damascus exchange — check end-of-day rates
☐ 16:00 — Review any manual rate overrides active today
☐ 17:00 — Verify anomaly detection system health
☐ 18:00 — Check ML prediction model accuracy (vs actual rates)
☐ 23:00 — Verify EOD settlement process started
☐ 23:30 — Review hedge positions and exposure
☐ 00:00 — Check daily reconciliation passed
☐ 01:00 — Confirm CBS report generation and archive
```

### Weekly Operations Checklist
```
☐ Monday 08:00 — Review provider scores and rankings
☐ Monday 09:00 — Update spread config if market conditions changed
☐ Monday 10:00 — Provider KPI review (uptime, accuracy, response time)
☐ Wednesday 10:00 — ML model accuracy review (retrain if needed)
☐ Friday 12:00 — Weekly hedge position report
☐ Friday 14:00 — Compliance report for suspicious FX patterns
☐ Sunday 03:00 — ML model retraining (automated)
```

### Monthly Operations
```
☐ 1st — Monthly CBS report generation
☐ 1st — Provider contract SLA review
☐ 5th — Monthly anomaly pattern analysis
☐ 10th — Spread optimization review (based on ML recommendations)
☐ 15th — Rate provider re-evaluation (add/remove providers)
☐ 20th — ML model retraining (if drift detected)
☐ Last day — Monthly financial reconciliation (FX revenue)
☐ Last day — Regulatory report submission to CBS
```

## Escalation Matrix

```
Level 1 (L1): Customer Support
  - Handle: Rate display questions, conversion failed explanation, lock expired
  - Tools: Admin panel read-only (view conversions, rates)
  - Escalation to L2: Balance discrepancy, failed conversion with money movement

Level 2 (L2): Operations Team / Treasury
  - Handle: Manual rate override, provider configuration, spread adjustment
  - Tools: Admin panel full access (override rates, manage providers)
  - Escalation to L3: All providers down, persistent anomaly, CFE issue

Level 3 (L3): Engineering
  - Handle: FX service bugs, Redis issues, CFE integration problems
  - Tools: Kubernetes, Redis CLI, DB access, logs
  - Escalation to L4: Architecture issues, security incidents

Level 4 (L4): CTO / Security Lead
  - Handle: Security breaches, regulatory escalations, major financial loss
  - Tools: Full system access, external communication authority
```

## SLA Targets

```
First Response Time:
  P0 — Complete FX Outage: 5 min (automated alert)
  P1 — Conversion Failures: 15 min (agent acknowledges)
  P2 — Rate Staleness: 30 min (ticket assigned)
  P3 — Provider Degradation: 4 hours (ticket assigned)

Resolution Time:
  P0: 30 min
  P1: 4 hours
  P2: 8 hours
  P3: 24 hours

System SLAs:
  Rate fetch interval: Every 15s (P99 < 30s)
  Rate display freshness: < 5s stale on UI
  Rate lock acquisition: P99 < 100ms
  Conversion execution: P99 < 3s (including CFE posting)
  Rate cache hit ratio: > 85%
  Provider online uptime: > 99.5%
  Rate anomaly detection latency: < 60s
  CBS report generation: Complete by 08:00 daily

Support Volume:
  Expected FX-related tickets: 50/day at 100K users
  Tier 1 resolution rate: > 80%
  CSAT target: > 90%
```

## Provider Management

### Adding a New Provider
```
1. Develop provider class implementing RateProviderInterface
2. Add provider config in database (fx_rate_providers)
3. Configure credentials via admin panel (encrypted)
4. Test in staging: 48h observation, verify rate quality
5. Add to production with priority 99 (lowest) for 24h
6. Monitor for 24h: rate accuracy, response time, uptime
7. If healthy: promote to appropriate priority tier
8. Add provider to health check monitoring
9. Update CBS report template if needed
```

### Removing a Provider
```
1. Set provider status to "inactive" (stops fetch immediately)
2. Notify ops team
3. If API provider: cancel subscription, revoke API keys
4. If scraper: remove scraper config, stop scraping pods
5. Archive provider data (keep for audit trail)
6. Update provider priority chain
7. Update CBS report if needed
```
