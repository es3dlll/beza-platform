# Loyalty Incident Response

## Incident Types

### P0: Points Balance Corruption
```
Description: Points balances incorrect (systematic error, >10% of users affected)
Impact: User trust destroyed, financial liability misstated
Response Time: 5 minutes
Team: Engineering on-call + Finance
```

### P1: Redemption Failures
```
Description: >5% of redemptions failing (API errors, provider issues)
Impact: Users cannot redeem points, frustration
Response Time: 15 minutes
Team: Backend engineer + Provider support
```

### P2: Tier Calculation Errors
```
Description: Users upgraded/downgraded incorrectly
Impact: Wrong fee applied, user confusion
Response Time: 1 hour
Team: Backend engineer
```

### P3: Reward Catalog Issues
```
Description: Missing images, wrong point costs, out of stock not reflected
Impact: Poor user experience
Response Time: 4 hours
Team: Content manager
```

## Runbook: P0 — Points Balance Corruption

### Step 1: Detection (0-2 min)
```
Alert: Grafana — points balance discrepancy > 1%
Check: Reconciliation query:
  SELECT SUM(amount) FROM loyalty_points WHERE expired_at IS NULL
  vs
  SELECT SUM(balance) FROM loyalty_points_balance
```

### Step 2: Triage (2-5 min)
```
1. Freeze points earning/redemption (maintenance mode)
2. Check batch job logs (tier upgrade, expiry) for errors
3. Check if recent deployment → rollback
4. Check database for corrupted entries
```

### Step 3: Mitigation (5-30 min)
```
[Points Balance Mismatch]
  1. Run full reconciliation script:
     SELECT user_id, SUM(amount) as calculated
     FROM loyalty_points WHERE expired_at IS NULL
     GROUP BY user_id
  2. Compare with loyalty_points_balance
  3. Correct mismatches via UPDATE
  4. Log all corrections for audit

[Double-Counting Points]
  1. Find duplicate source_transaction_id entries
  2. Deduplicate, reverse excess credits
  3. Notify affected users if net change

[Data Corruption]
  1. Restore from last known good snapshot (previous day)
  2. Reprocess transactions from backup
  3. Recalculate balances
```

### Step 4: Recovery
```
1. Verify all balances match after correction
2. Run tier recalculation for affected users
3. Notify users of any balance changes
4. Resume points earning/redemption
5. Post-mortem within 24 hours
```

## Runbook: P1 — Redemption Failures

### Investigation
```
1. Check error distribution:
   SELECT error_code, COUNT(*) FROM loyalty_redemptions
   WHERE created_at > NOW() - INTERVAL 15 MINUTE
   AND status = 'failed'
   GROUP BY error_code;

2. Common patterns:
   - Airtime provider API down → escalate to provider
   - Insufficient stock → update reward catalog
   - PIN verification failures → check auth service
   - Points balance race condition → check concurrent requests
```

### Fixes
```
[Provider API Down]
  → Queue redemptions for retry
  → Notify users: "سيتم شحن الرصيد خلال 24 ساعة"
  → Switch to alternative provider if available

[Insufficient Stock]
  → Hide reward from catalog
  → Notify product team to restock
  → Offer alternative reward of equal value
```

## Post-Mortem Template
```markdown
# Post-Mortem: Loyalty [TITLE]

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
- Users affected: XX
- Points incorrectly credited/debited: XX
- Financial impact: XX SYP

## Actions
| Action | Owner | Due Date |
|--------|-------|----------|
| Fix root cause | Engineer | YYYY-MM-DD |
| Add monitoring | DevOps | YYYY-MM-DD |
| User communication | Support | YYYY-MM-DD |
| Update runbook | Engineer | YYYY-MM-DD |

## Lessons Learned
[What went well, what went wrong, what to improve]
```
