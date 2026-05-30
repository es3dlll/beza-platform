# Remittance Incident Response

## Incident Types

### P0: Complete Remittance Outage
```
Description: All remittance operations failing (>90% error rate)
Impact: Users cannot send money, family financial support disrupted
Response Time: 5 minutes
Team: Engineering on-call + DevOps + FX team
Runbook: See below
```

### P1: Corridor-Specific Outage
```
Description: One corridor failing (>10% error rate for specific corridor)
Impact: Diaspora from that country cannot send money
Response Time: 15 minutes
Team: Backend engineer + FX engineer + Compliance
```

### P2: FX Rate Stale or Incorrect
```
Description: FX rates not updating (>30s stale) or incorrect (>1% deviation)
Impact: Financial loss from incorrect rates, user complaints
Response Time: 30 minutes
Team: FX engineer + Backend engineer
```

### P3: Recurring Execution Failures
```
Description: >10% of recurring transfers failing
Impact: Sender frustration, missed family support
Response Time: 1 hour
Team: Backend engineer
```

## Runbook: P0 — Complete Remittance Outage

### Step 1: Detection (0-2 min)
```
Alert: PagerDuty notification
Check: Grafana dashboard
  - Remittance API error rate > 90%
  - FX engine response time > 10s
  - Compliance screening response time > 30s
  - Queue depth growing rapidly
```

### Step 2: Triage (2-5 min)
```
1. Check if deployment occurred recently → rollback if < 30 min ago
2. Check FX engine health:
   - `curl -f http://fx-engine/health`
   - FX engine logs for errors
   - Check FX provider connectivity (XE, OANDA)
3. Check compliance service:
   - `curl -f http://compliance-service/health`
   - Sanctions list download status
   - World-Check API connectivity
4. Check database health:
   - `SHOW PROCESSLIST;` — any long-running queries?
   - `SELECT * FROM information_schema.INNODB_TRX;` — any deadlocks?
5. Check Redis for FX rate locks:
   - `redis-cli DBSIZE` (should be < 10,000)
   - `redis-cli INFO memory`
```

### Step 3: Mitigation (5-15 min)
```
Common Causes & Fixes:

[FX Engine Down]
  → Restart FX engine pod: kubectl rollout restart deployment/fx-engine
  → If unavailable > 2 min, enable degraded mode:
    - Use 5-min old cached rates (with banner: "السعر قديم")
    - Disable rate lock feature
    - Limit to local P2P only (no FX needed)

[Compliance Service Down]
  → Restart compliance service pod
  → If unavailable > 5 min, enable relaxed mode:
    - Skip sanctions screening (log for batch processing)
    - Flag all transfers for post-processing review
    - Notify compliance team

[Database Connection Exhausted]
  → Check max_connections: SHOW VARIABLES LIKE 'max_connections';
  → Kill idle connections
  → Scale up connection pool

[Redis Failure]
  → Failover to Redis replica
  → If all Redis down, disable rate locks (require instant execution)
  → Transfer history falls back to DB query (slow)

[Correspondent Bank API Down]
  → Notify finance team
  → Queue settlement for later processing
  → Remittances still process (real-time wallet movement is internal)
```

### Step 4: Recovery (15-30 min)
```
1. Verify all services healthy
2. Run reconciliation: match remittances to wallet transactions
3. Process any queued compliance screenings
4. Process pending recurring transfers
5. Notify users of any affected transactions
6. Post-mortem within 24 hours
```

## Runbook: P1 — Corridor Outage (e.g., EUR_DE->SYP)

### Investigation
```
1. Check corridor status:
   SELECT * FROM remittance_corridors WHERE corridor_key = 'EUR_DE->SYP';
   → Is status = 'active'? Is maintenance_message set?

2. Check if FX rate for that corridor is updating:
   SELECT * FROM fx_rate_logs WHERE corridor_id = 1 ORDER BY created_at DESC LIMIT 10;
   → Is rate updating every second?

3. Check if specific provider failed:
   - XE.com EUR→SYP rate check
   - Deutsche Bank connectivity

4. Check sender country blocking:
   - Any new German regulatory changes?
   - BAFin notices?
```

### Fixes
```
[FX Provider Issue for Specific Currency]
  → Failover to secondary provider (XE → OANDA)
  → If both down, use 15-min cached rate with warning

[Correspondent Bank Maintenance]
  → Set corridor to maintenance mode
  → Display maintenance message: "الممر قيد الصيانة، يرجى المحاولة لاحقاً"
  → Notify affected users via push

[Regulatory Block]
  → Immediate: Set corridor to inactive
  → Compliance: Investigate regulatory reason
  → Communication: Draft user notification
```

## Post-Mortem Template
```markdown
# Post-Mortem: [TITLE]

Date: YYYY-MM-DD
Duration: XX minutes
Severity: P0/P1/P2
Corridor Affected: [all / specific]
Users Affected: XX
Financial Impact: $XX

## Timeline
- HH:MM — Alert triggered
- HH:MM — Engineer acknowledged
- HH:MM — Root cause identified
- HH:MM — Mitigation applied
- HH:MM — Service restored

## Root Cause
[Detailed description of what caused the incident]

## Impact
- Remittances failed: XX
- Recurring transfers missed: XX
- Users affected: XX
- Revenue loss: $XX
- FX exposure: $XX

## Actions
| Action | Owner | Due Date |
|--------|-------|----------|
| Fix root cause | Engineer | YYYY-MM-DD |
| Add monitoring | DevOps | YYYY-MM-DD |
| Update runbook | Engineer | YYYY-MM-DD |
| Test fix | QA | YYYY-MM-DD |
| Compliance review | Compliance | YYYY-MM-DD |

## Lessons Learned
[What went well, what went wrong, what to improve]
```
