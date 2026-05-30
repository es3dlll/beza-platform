# Cards Incident Response

## Incident Types

### P0: Card Processor Down
```
Description: Card processor unreachable — all card auths failing
Impact: Users cannot pay with cards, all auths declined
Response Time: 5 minutes
Team: Card engineering on-call + DevOps + Switch engineer
```

### P1: High Auth Decline Rate
```
Description: >15% of authorizations declined (normally <5%)
Impact: User frustration, merchant dissatisfaction, revenue loss
Response Time: 15 minutes
Team: Card backend engineer + Fraud analyst
```

### P2: HSM Degradation
```
Description: HSM response time > 500ms (normally <50ms)
Impact: Slow PIN verification, delayed card operations
Response Time: 30 minutes
Team: Security engineer + HSM vendor
```

### P3: Settlement Delay
```
Description: Settlement batch not processed by 03:00
Impact: Delayed merchant/ATM settlement, financial reconciliation fail
Response Time: 1 hour
Team: Card backend engineer
```

## Runbook: P0 — Card Processor Down

### Step 1: Detection (0-2 min)
```
Alert: PagerDuty notification
Check: Grafana dashboard
  - Auth success rate ≈ 0%
  - Card processor health check failing
  - ISO 8583 connection status: DISCONNECTED
```

### Step 2: Triage (2-5 min)
```
1. Check if deployment occurred recently → rollback if < 30 min ago
2. Check card processor service:
   - kubectl get pods -n cards
   - kubectl logs -n cards deployment/card-processor --tail=50
3. Check switch connectivity:
   - curl -f http://switch-health.local/status
   - Check VPN/tunnel to local switch
4. Check database:
   - SHOW PROCESSLIST — any deadlocks on card_transactions?
   - Check disk space on card tables
```

### Step 3: Mitigation (5-15 min)
```
[Card Processor Crash]
  → Restart: kubectl rollout restart deployment/card-processor
  → If crash looping: check recent code changes, rollback image
  → If OOM: increase memory limit, check memory leak

[Switch Connection Lost]
  → Restart VPN tunnel: systemctl restart switch-vpn
  → Check firewall rules (switch IP whitelisted?)
  → Contact switch operator for network status

[Database Connection Issue]
  → Check max_connections: SHOW VARIABLES LIKE 'max_connections'
  → Kill idle connections
  → Scale connection pool

[HSM Failure (cascading to processor)]
  → Failover to standby HSM
  → Check HSM appliance status via management console
```

### Step 4: Recovery (15-30 min)
```
1. Verify processor health: GET /health → 200 OK
2. Process queued auths (if any) — likely all timed out
3. Verify switch connection: send test ISO 8583 echo message
4. Send test auth with test card
5. Monitor: auth rate, latency, error rate for 5 min
6. Notify operations team that service restored
7. Post-mortem within 24 hours
```

### Step 5: If Recovery Fails (30+ min)
```
Declare extended outage:
  1. Set maintenance page on card endpoints
  2. Notify users: "خدمة البطاقات غير متاحة حالياً — نعمل على إصلاحها"
  3. Notify merchant acquirers and BIN sponsor
  4. Consider manual switch to read-only mode (balance checks only)
  5. Prepare manual settlement file if needed
```

## Runbook: P1 — High Auth Decline Rate

### Investigation
```
1. Check decline reasons in last 15 min:
   SELECT decline_reason, COUNT(*)
   FROM card_transactions
   WHERE created_at > NOW() - INTERVAL 15 MINUTE
   AND status = 'declined'
   GROUP BY decline_reason;

2. Common patterns:
   - "limit_exceeded": Check if limits were recently changed globally
   - "insufficient_balance": Check card wallet balance calculation
   - "card_frozen": Check if bulk freeze was triggered (fraud?)
   - "fraud_declined": Check fraud model confidence, recent rule change
   - "invalid_cvv": Check HSM CVV verification

3. Check if specific BIN/merchant/country affected:
   SELECT merchant_country, COUNT(*) FROM card_transactions
   WHERE ... AND status = 'declined' GROUP BY merchant_country;
```

### Fixes
```
[Fraud Model False Positive]
  → Temporarily lower fraud model sensitivity (raise threshold from 70 → 85)
  → Review recent fraud rule changes
  → Check if BIN attack protection rules too aggressive

[Limit System Bug]
  → Check if daily counter reset failed
  → Check if new limits deployed incorrectly
  → Manual reset of affected card daily counters

[BIN Sponsor Issue]
  → Check sponsor's transaction handling
  → Temporarily route international txns through backup sponsor
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
[Detailed description]

## Impact
- Auths affected: XX
- Declined auths: XX (false declines)
- Cards affected: XX
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
