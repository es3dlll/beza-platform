# Bill Payment Incident Response

## Incident Types

### P0: Complete Bill Payment Outage
```
Description: All bill payment operations failing (>90% error rate)
Impact: Users cannot fetch or pay any bills
Response Time: 5 minutes
Team: Backend on-call + Biller integration engineer
```

### P1: Single Biller Outage
```
Description: One biller API completely down (>50% errors)
Impact: Users cannot pay that specific biller's bills
Response Time: 15 minutes
Team: Backend engineer + Biller-specific integration owner
```

### P2: Payment Confirmation Delays
```
Description: Biller confirms payment > 5 minutes
Impact: Users see "pending" status instead of "paid"
Response Time: 1 hour
Team: Backend engineer
```

### P3: CSV Batch Processing Failure
```
Description: Government/university CSV not processed
Impact: Users cannot view/fetch CSV-based bills
Response Time: 4 hours
Team: Data engineer
```

## Runbook: P0 — Complete Bill Payment Outage

### Step 1: Detection (0-2 min)
```
Alert: PagerDuty notification
Check: Grafana dashboard
  - Bill payment error rate > 90%
  - All biller API latencies > 10s
  - Kong gateway errors spiking
```

### Step 2: Triage (2-5 min)
```
1. Check if recent deployment → rollback if < 30 min ago
2. Check Kong gateway health: `curl -f http://kong-admin:8001/status`
3. Check biller API gateway:
   - `curl -f http://biller-api-gateway/health`
   - Check circuit breaker status for each biller
4. Check database:
   - `SHOW PROCESSLIST;`
   - `SELECT * FROM information_schema.INNODB_TRX;`
5. Check queue:
   - RabbitMQ management console
   - Queue depth for bill-payment queue
```

### Step 3: Mitigation (5-15 min)
```
Common Causes & Fixes:

[Biller API Gateway Down]
  → Restart Kong: kubectl rollout restart deployment/kong
  → Check Kong config: did a biller route config change?

[Database Connection Pool Exhausted]
  → Check max_connections
  → Kill idle connections
  → Scale up connection pool (increase Octane workers)

[All Biller APIs Unreachable]
  → Check if firewall/NAT changed
  → Check outbound internet connectivity
  → If DNS issue: flush DNS cache
  → If ISP/Syria Telecom backbone issue: contact ISP

[Queue Backup]
  → Scale workers: kubectl scale deployment/bill-worker --replicas=10
  → Clear poison messages from DLQ

Degraded Mode (if cannot fix within 10 min):
  → Disable bill payment temporarily
  → Show maintenance page: "خدمة دفع الفواتير قيد الصيانة"
  → Notify users via in-app banner
  → Enable once biller connectivity restored
```

### Step 4: Recovery (15-30 min)
```
1. Verify all services healthy
2. Process queued payments from degraded mode
3. Run reconciliation: match bill_transactions to CFE postings
4. Check for any payments made during outage that need confirmation
5. Notify users of affected payments
6. Post-mortem within 24 hours
```

## Runbook: P1 — Single Biller Outage

### Investigation
```
1. Check biller-specific metrics:
   - Grafana: "Biller API Health" panel
   - Error rate for that biller
   - Latency P95/P99 for that biller

2. Test biller API directly:
   curl -X POST https://api.peed.gov.sy/v1/bill/inquiry \
     -H "Authorization: Bearer $API_KEY" \
     -d '{"customer_id": "test_id"}'

3. Check circuit breaker status:
   kubectl exec deploy/biller-api-gateway -- \
     curl http://localhost:8081/circuit-breaker/peed

4. Contact biller technical contact:
   PEED: +963-11-XXXXXXX (IT Support)
   Syriatel: +963-11-XXXXXXX (B2B Support)
   MTN: +963-11-XXXXXXX (Partner Support)
```

### Mitigation
```
[Circuit Breaker Open]
  → Circuit opens after 5 failures in 60s
  → Auto-closes after 30s (half-open → 3 test requests)
  → Manual reset if needed:
    curl -X POST http://biller-api-gateway:8081/circuit-breaker/peed/reset

[Biller API Returning Errors]
  → If biller maintenance: set biller status to 'maintenance' in DB
  → Show message: "خدمة [$biller] قيد الصيانة — يرجى المحاولة لاحقاً"
  → Queue payments for retry when biller returns

[Biller API Changed]
  → Check if biller changed API contract (version bump?)
  → Rollback to previous integration version
  → Contact biller for API changes documentation

Temporary Workaround (for CSV billers only):
  → If government CSV FTP unavailable:
    - Accept payment in manual mode
    - Record transaction as 'pending'
    - Reconcile when CSV file arrives
    - Notify user: "سيتم تأكيد الدفع خلال 48 ساعة"
```

## Runbook: P2 — Payment Confirmation Delays

### Investigation
```
1. Identify affected bills:
   SELECT * FROM bill_transactions
   WHERE status = 'pending'
   AND created_at > NOW() - INTERVAL 1 HOUR;

2. Check biller status for each:
   - Did biller receive the payment request?
   - Check biller_connection_logs for the payment call

3. Check CFE status:
   - Was the wallet debited?
   - Is the hold still active?
```

### Fixes
```
[Biller Confirmed but Status Not Updated]
  → Manual update: UPDATE bill_transactions SET status = 'paid'
    WHERE reference = 'BILL-XXXX'
  → Or: trigger webhook re-play

[CFE Hold Active but Biller Not Confirmed]
  → Release CFE hold → money returns to user
  → Mark transaction as failed
  → Notify user: "تم إرجاع المبلغ إلى محفظتك"

[Auto-pay Stuck]
  → Check auto-pay worker logs
  → Restart auto-pay worker if hung
  → Manually trigger auto-pay for affected schedules:
    php artisan bills:auto-pay --schedule-id=123
```

## Runbook: P3 — CSV Batch Processing Failure

### Investigation
```
1. Check CSV batch file status:
   SELECT * FROM csv_batch_files ORDER BY created_at DESC LIMIT 5;

2. Check FTP connection:
   sftp csv@csv.gateway.gov.sy
   Check if file exists in the expected directory

3. Check CSV parser logs:
   storage/logs/csv-parser-2026-06-10.log
```

### Fixes
```
[FTP Connection Failed]
  → Check credentials in Vault
  → Check if source IP is whitelisted
  → Contact ministry IT to verify FTP status

[CSV Format Changed]
  → Compare new CSV headers with expected
  → Update parser configuration in billers.config
  → Re-process: php artisan bills:csv:reprocess --batch-id=15

[Parser Failed on Specific Records]
  → Export failed records to review file
  → Fix data issues upstream
  → Skip bad records and process the rest
  → Manually add corrected records

[File Not Received on Expected Schedule]
  → Government CSV: expected daily by 03:00
  → University CSV: expected weekly on Sunday
  → If > 2 hours late: contact source
  → Set system to show: "البيانات غير متوفرة حالياً" for affected billers
```

## Post-Mortem Template
```markdown
# Post-Mortem: [TITLE — e.g., PEED API Outage]

Date: YYYY-MM-DD
Duration: XX minutes
Severity: P0/P1/P2
Biller(s) Affected: [peed, syriatel, etc.]
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
- Failed bill fetches: XX
- Failed bill payments: XX
- Financial impact: XX SYP (refunds, lost commissions)
- Biller settlement delay: XX hours

## Actions
| Action | Owner | Due Date |
|--------|-------|----------|
| Fix root cause | Engineer | YYYY-MM-DD |
| Add monitoring | DevOps | YYYY-MM-DD |
| Update runbook | Engineer | YYYY-MM-DD |
| Test fix | QA | YYYY-MM-DD |
| Contact biller re SLA | Ops | YYYY-MM-DD |

## Lessons Learned
[What went well, what went wrong, what to improve]
```
