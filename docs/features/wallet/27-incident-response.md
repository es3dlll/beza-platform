# Wallet Incident Response

## Incident Types

### P0: Complete Wallet Outage
```
Description: All wallet operations failing (>90% error rate)
Impact: Users cannot send, receive, or check balance
Response Time: 5 minutes
Team: Engineering on-call + DevOps
```

### P1: Transaction Failures
```
Description: >5% of transfers failing
Impact: User frustration, revenue loss
Response Time: 15 minutes
Team: Backend engineer + CFE engineer
```

### P2: Latency Degradation
```
Description: P99 > 5s on transfer endpoint
Impact: Poor UX, potential timeouts
Response Time: 1 hour
Team: Backend engineer
```

### P3: Balance Display Issues
```
Description: Balance not updating, showing stale data
Impact: User confusion
Response Time: 4 hours
Team: Backend engineer
```

## Runbook: P0 — Wallet Outage

### Step 1: Detection (0-2 min)
```
Alert: PagerDuty notification
Check: Grafana dashboard
  - Wallet API error rate > 90%
  - CFE response time > 10s
  - Queue depth growing rapidly
```

### Step 2: Triage (2-5 min)
```
1. Check if deployment occurred recently → rollback if less than 30 min ago
2. Check database health:
   - `SHOW PROCESSLIST;` — any long-running queries?
   - `SELECT * FROM information_schema.INNODB_TRX;` — any deadlocks?
3. Check CFE service:
   - Is CFE responding? → `curl -f http://cfe-service/health`
   - CFE logs for errors
4. Check Redis:
   - `redis-cli PING`
   - Memory usage: `redis-cli INFO memory`
```

### Step 3: Mitigation (5-15 min)
```
Common Causes & Fixes:

[Database Connection Exhausted]
  → Check max_connections: SHOW VARIABLES LIKE 'max_connections';
  → Kill idle connections: SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE state = 'idle';
  → Scale up connection pool

[CFE Service Down]
  → Restart CFE pod: kubectl rollout restart deployment/cfe-service
  → If unavailable for >5 min, enable degraded mode:
    - Bypass CFE hold (only for balance checks)
    - Queue transfers for later processing
    - Set maintenance mode page

[Queue Backup]
  → Scale workers: kubectl scale deployment/wallet-worker --replicas=20
  → Clear poison messages from DLQ

[Redis Failure]  
  → Failover to Redis replica
  → If all Redis down, serve stale balance from DB (slow mode)
```

### Step 4: Recovery (15-30 min)
```
1. Verify all services healthy
2. Run reconciliation: match DB transactions to CFE postings
3. Process any queued transactions from degraded mode
4. Notify users of any affected transactions
5. Post-mortem within 24 hours
```

## Runbook: P1 — Transaction Failures

### Investigation
```
1. Check error types:
   SELECT failure_reason, COUNT(*) FROM wallet_transactions
   WHERE created_at > NOW() - INTERVAL 15 MINUTE
   AND status = 'failed'
   GROUP BY failure_reason;

2. Common patterns:
   - "insufficient_balance": Check if balance calculation is correct
   - "invalid_recipient": Check user lookup service
   - "cfe_timeout": Check CFE response times
   - "fraud_blocked": Check fraud model confidence threshold
```

### Fixes
```
[CFE Timeout]
  → Check CFE database CPU
  → Increase CFE timeout from 5s to 10s temporarily
  → Scale CFE pods

[Fraud False Positive]
  → Temporarily lower fraud model threshold
  → Review fraud rules for over-matching
```

## Post-Mortem Template
```markdown
# Post-Mortem: [TITLE]

Date: YYYY-MM-DD
Duration: XX minutes
Severity: P0/P1/P2
Summary: One-line description

## Timeline
- HH:MM — Alert triggered
- HH:MM — Engineer acknowledged
- HH:MM — Root cause identified
- HH:MM — Mitigation applied
- HH:MM — Service restored

## Root Cause
[Detailed description of what caused the incident]

## Impact
- Users affected: XX
- Transactions failed: XX
- Financial impact: XX SYP

## Actions
| Action | Owner | Due Date |
|--------|-------|----------|
| Fix root cause | Engineer | YYYY-MM-DD |
| Add monitoring | DevOps | YYYY-MM-DD |
| Update runbook | Engineer | YYYY-MM-DD |
| Test fix | QA | YYYY-MM-DD |

## Lessons Learned
[What went well, what went wrong, what to improve]
```
