# Merchant Incident Response

## Incident Types

### P0: Merchant Payment Outage
```
Description: All merchant payment methods failing (>90% error rate)
Impact: Merchants cannot accept payments, revenue stops
Response Time: 5 minutes
Team: Engineering on-call + DevOps
```

### P1: Settlement Failure
```
Description: Daily settlement job fails or is delayed > 2 hours
Impact: Merchants don't get paid, trust erosion, potential regulatory issue
Response Time: 15 minutes
Team: Backend engineer + Finance ops
```

### P1: QR Code Generation Failure
```
Description: QR generation endpoint down or returning errors
Impact: New merchants cannot get QR codes, existing QR serving still works (CDN)
Response Time: 15 minutes
Team: Backend engineer
```

### P2: Webhook Delivery Degradation
```
Description: Webhook delivery rate drops below 95% for > 10 minutes
Impact: E-commerce merchants don't get real-time payment confirmation
Response Time: 1 hour
Team: Backend engineer
```

### P2: POS Terminal Sync Issues
```
Description: POS terminals cannot sync transactions for > 30 minutes
Impact: Transactions queue on terminal, delayed settlement
Response Time: 1 hour
Team: Backend engineer + POS team
```

### P3: Merchant Dashboard Slow
```
Description: Dashboard loading > 5 seconds
Impact: Poor merchant experience
Response Time: 4 hours
Team: Backend engineer
```

## Runbook: P0 — Merchant Payment Outage

### Step 1: Detection (0-2 min)
```
Alert: PagerDuty notification
Check: Grafana dashboard
  - Merchant API error rate > 90%
  - CFE response time > 10s
  - All payment methods failing
```

### Step 2: Triage (2-5 min)
```
1. Check if deployment occurred recently → rollback if < 30 min ago
2. Check database health:
   - `SHOW PROCESSLIST;` — any long-running queries?
   - Check merchant_transactions table for locks
3. Check QR service:
   - Is QR generator responding? → `curl -f http://qr-service/health`
   - Is CDN serving QR images? → `curl -I https://cdn.beza.com/merchant/1/qr_static.png`
4. Check CFE service:
   - Is CFE responding? → `curl -f http://cfe-service/health`
5. Check Redis:
   - `redis-cli PING`
   - Merchant cache keys
```

### Step 3: Mitigation (5-15 min)
```
Common Causes & Fixes:

[QR Service Down]
  → Restart QR generation pod: kubectl rollout restart deployment/qr-generator
  → If QR gen down > 5 min:
    - Static QR images still served from CDN (no impact on existing merchants)
    - New QR generation fails → communicate via merchant app banner
    - Fallback: Serve last generated QR from DB record

[CFE Service Down]
  → Restart CFE: kubectl rollout restart deployment/cfe-service
  → If unavailable > 5 min, enable degraded mode:
    - Queue merchant payments for later processing
    - Display "نظام الدفع قيد الصيانة" for customers
    - POS terminals queue transactions locally

[Database Slow]
  → Check for table locks: SHOW OPEN TABLES WHERE In_use > 0
  → Kill long-running queries
  → Scale up DB: Increase resources via RDS/Cloud SQL console

[API Rate Limiting]
  → Check Kong rate limit counters
  → Temporarily increase limits if legitimate traffic surge
  → Check for DDoS pattern (same IP, rapid requests)
```

### Step 4: Recovery (15-30 min)
```
1. Verify merchant API healthy (smoke test: create link, get QR, list txns)
2. Process any queued payments from degraded mode
3. Run settlement check: any merchants missing yesterday's settlement?
4. Check POS terminal sync: any terminals with unsent transactions?
5. Notify merchants via app banner: "جميع الخدمات تعمل بشكل طبيعي"
6. Post-mortem within 24 hours
```

## Runbook: P1 — Settlement Failure

### Investigation
```
1. Check settlement job logs:
   kubectl logs job/process-settlement --tail=100

2. Check for specific merchant failures:
   SELECT merchant_id, COUNT(*) FROM merchant_transactions
   WHERE date = YESTERDAY AND settled = false
   GROUP BY merchant_id;

3. Check CFE transfer status:
   SELECT * FROM cfe_transactions
   WHERE reference LIKE 'settlement_%'
   AND date = YESTERDAY
   AND status = 'failed';

4. Common causes:
   - Merchant wallet frozen: Check merchant.status
   - CFE insufficient clearing balance: Check CFE settlement account
   - Database deadlock: Retry usually resolves
   - Settlement already completed: Duplicate run prevention
```

### Fixes
```
[Merchant Wallet Frozen]
  → Unfreeze wallet if compliance issue resolved
  → If frozen permanently: Manual settlement to bank account
  → Notify merchant: "تعذرت التسوية بسبب تجميد المحفظة"

[CFE Insufficient Balance]
  → Top up CFE settlement clearing account
  → Retry settlement manually via admin panel
  → Add alert for low CFE clearing balance

[Database Deadlock]
  → Auto-retry mechanism should resolve (3 attempts)
  → If persistent: Check for slow queries, add indexes
  → Manual trigger: Admin panel "إعادة محاولة التسوية"

[Partial Settlement (some merchants succeeded, some failed)]
  → Run settlement for failed merchants only:
    php artisan merchant:settlement --merchant-ids=42,55,78
  → Verify all merchants settled after manual run
```

## Post-Mortem Template
```markdown
# Merchant Feature Post-Mortem

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
- Merchants affected: XX
- Failed transactions: XX
- Unsettled amount: XX SYP
- Revenue impact: XX SYP

## Actions
| Action | Owner | Due Date |
|--------|-------|----------|
| Fix root cause | Engineer | YYYY-MM-DD |
| Add monitoring | DevOps | YYYY-MM-DD |
| Update runbook | Engineer | YYYY-MM-DD |
| Test fix | QA | YYYY-MM-DD |
| Communicate to affected merchants | Support | YYYY-MM-DD |

## Lessons Learned
[What went well, what went wrong, what to improve]
```
