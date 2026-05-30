# Runbook: Settlement Batch Failure

## Severity: P0

## Symptoms

- **Alerts:**
  - Grafana alert `settlement_batch_failed` fires when a settlement batch transitions to `FAILED` status
  - PagerDuty: "SETTLEMENT_BATCH_FAILED — Batch {batch_id} — {amount} SYP — {bank} — {error_message}"
  - Slack #settlement-alerts: "🚨 Batch {batch_id} failed after {retry_count} retries. Amount: {amount} SYP. Bank: {bank}"
  - CBS non-compliance risk if settlement fail > 2h (per CBS decree 2024/12 real-time settlement mandate)
  - Datadog metric `settlement.batch.failure.count` spikes

- **What users see:**
  - Merchants: pending settlement status shows "قيد المعالجة" (Processing) beyond expected T+1 timeline
  - Agents: pending commission payouts delayed
  - Billers: payment confirmations not received — biller may resend or dispute
  - Customers: if settlement to external wallet, funds not credited ("المبلغ لم يتم إيداعه بعد")
  - Internal Finance Ops: settlement screen shows red "FAILED" badge on batch

- **What dashboards show:**
  - `https://grafana.beza-sy.com/d/settlement` — Settlement success rate drops below 95%
  - `https://grafana.beza-sy.com/d/settlement-batches` — Failed batch highlighted in red with error details
  - `https://metabase.beza-sy.com/dashboard/settlement-recon` — Pending unreconciled settlement amounts

## Immediate Actions (First 5 min)

1. **Acknowledge the alert in PagerDuty:**
   ```
   pd acknowledge -i <incident_id>
   ```

2. **Identify the failed batch and reason:**
   ```sql
   SELECT sb.id, sb.total_amount, sb.status, sb.bank, sb.error_message, sb.retry_count,
          sb.created_at, sb.last_attempt_at
   FROM settlement.batches sb
   WHERE sb.status = 'FAILED' AND sb.last_attempt_at > NOW() - INTERVAL '30 minutes'
   ORDER BY sb.total_amount DESC;
   ```

3. **Check CBS RTGS system status (if interbank settlement):**
   - URL: `https://srm.cbs.gov.sy/rtgs/status`
   - Verify settlement window is open (09:00–14:00 Sunday–Thursday)
   - If RTGS is down, call CBS operations: +963 11 245 8900

4. **Check the specific bank's settlement portal:**
   - BSO: `https://ebanking.bso-sy.com` — check pending transfers
   - Bemo: `https://corporate.bemo-sy.com` — check settlement queue
   - SIIB: `https://ebanking.siib-sy.com` — check scheduled batches
   - CBS: Contact CBS settlement desk directly: +963 11 245 8915

5. **Check settlement service logs:**
   ```
   kubectl logs -n settlement -l app=settlement-engine --tail 200 --since=1h | grep -i error
   ```

## Investigation Steps

1. **Open settlement monitoring dashboard:**
   - URL: `https://grafana.beza-sy.com/d/settlement`
   - Check "Batch Success Rate" panel — is this a single batch failure or system-wide?
   - Check "Bank Status" panel — which bank(s) are impacted?
   - Check "Settlement Latency" panel — are batches queuing up?

2. **Examine the batch failure details:**
   ```sql
   SELECT sb.*, sbe.event_type, sbe.event_data, sbe.created_at
   FROM settlement.batches sb
   JOIN settlement.batch_events sbe ON sb.id = sbe.batch_id
   WHERE sb.id = '<failed_batch_id>'
   ORDER BY sbe.created_at;
   ```

3. **Check if there are pending batches queued behind this failure:**
   ```sql
   SELECT COUNT(*), SUM(total_amount) 
   FROM settlement.batches 
   WHERE status = 'PENDING' AND created_at > NOW() - INTERVAL '4 hours';
   ```

4. **Verify bank account balances are sufficient:**
   ```sql
   SELECT ba.account_name, ba.bank, ba.balance, ba.available_balance
   FROM treasury.bank_accounts ba
   WHERE ba.account_type IN ('TIER1_CASH', 'TIER2_BANK');
   ```

5. **Check if this is a known CBS blackout period:**
   - CBS settlement windows: 09:00–14:00 Sun–Thu
   - No settlement on Fridays or Syrian public holidays (check holiday calendar)
   - If outside window, batch will auto-retry at next window

6. **Check the CBS SRM settlement queue:**
   ```
   curl -s -H "Authorization: Bearer <cbs-srm-token>" \
     https://srm.cbs.gov.sy/api/v1/settlement/queue | jq '.pending[] | select(.batch_id=="<batch_id>")'
   ```

## Resolution Steps

1. **If CBS RTGS is down (systemic):**
   - Wait for CBS to restore RTGS (typically 30–60 min)
   - Batches will auto-retry every 15 min (max 10 retries)
   - Call CBS operations desk: +963 11 245 8900 — get ETA
   - If > 2h, escalate to CEO — request manual settlement via CBS paper system
   - Prepare manual settlement instructions:
     ```
     To: CBS Settlement Department
     Subject: Urgent — Manual settlement request — Incident {incident_id}
     Batch ID: {batch_id}
     Amount: {amount} SYP
     Beneficiary Bank: {bank}
     Beneficiary Account: {account}
     Authorization: {CFO name}, {CEO name}
     ```

2. **If bank account insufficient balance:**
   - Check Tier 1 cash at CBS, initiate transfer to affected bank:
     ```sql
     BEGIN;
     UPDATE treasury.bank_accounts 
     SET balance = balance - <required_amount>
     WHERE account_name = 'CBS_SETTLEMENT';
     
     INSERT INTO treasury.transfers 
     (from_account, to_account, amount, currency, purpose, authorized_by)
     VALUES ('CBS_SETTLEMENT', '<affected_bank_account>', <required_amount>, 'SYP', 
             'Settlement top-up — incident {incident_id}', '<your_name>');
     COMMIT;
     ```
   - Execute via CBS RTGS or bank portal
   - Confirm balance updated, retry batch

3. **If batch format error (invalid IBAN, amount mismatch):**
   - Examine error_message for field-level details
   - Correct the settlement file:
     ```sql
     UPDATE settlement.batches
     SET error_message = NULL, status = 'PENDING', retry_count = 0
     WHERE id = '<batch_id>';
     ```
   - If data issue in source, fix upstream data:
     ```sql
     -- Example: fix invalid IBAN
     UPDATE settlement.batch_items
     SET beneficiary_iban = '<corrected_iban>'
     WHERE batch_id = '<batch_id>' AND beneficiary_name = '<name>';
     ```
   - Trigger retry:
     ```
     curl -X POST https://beza-api.internal/settlement/batches/<batch_id>/retry \
       -H "Authorization: Bearer <settlement-token>"
     ```

4. **If bank portal/API reject (bank-side issue):**
   - Contact bank settlement desk:
     - BSO: Omar Al-Khatib +963 11 235 1122
     - Bemo: Sami Daoud +963 11 236 3344
     - SIIB: Hassan Mousa +963 11 237 5566
     - CBS: Settlement Ops +963 11 245 8915
   - Ask for specific rejection reason
   - Request manual override if bank-side flag issue
   - Once resolved, retry batch:
     ```
     curl -X POST https://beza-api.internal/settlement/batches/<batch_id>/retry
     ```

5. **Verify batch settles successfully:**
   ```sql
   SELECT id, total_amount, status, completed_at
   FROM settlement.batches
   WHERE id = '<batch_id>';
   ```
   - Confirm on bank portal that funds were received
   - Check reconciliation dashboard: `https://grafana.beza-sy.com/d/recon-external`

6. **Handle downstream effects (reconciliation, notification):**
   - Reconciliation engine auto-matches settled batch within 15 min
   - If auto-recon fails, trigger manual recon:
     ```
     curl -X POST https://beza-api.internal/recon/trigger?type=external&batch_id=<batch_id>
     ```
   - Send settlement confirmation to affected merchants/agents:
     ```
     curl -X POST https://beza-api.internal/notifications/settlement-complete \
       -H "Authorization: Bearer <ops-token>" \
       -d '{"batch_id": "<batch_id>"}'
     ```

## Rollback Plan

- **If batch settled to wrong account:**
  1. Immediate contact to receiving bank to freeze funds
  2. Initiate recall request via CBS RTGS (time-sensitive, must be same day before 14:00)
  3. If after 14:00, recall must wait until next business day
  4. File loss report with compliance if amount > 500K SYP
  5. Create treasury chargeback entry:
     ```sql
     INSERT INTO treasury.chargebacks 
     (batch_id, amount, reason, status, reported_at)
     VALUES ('<batch_id>', <amount>, 'Incorrect account settlement', 'INVESTIGATING', NOW());
     ```

- **If batch was double-settled:**
  1. Identify duplicate settlement records:
     ```sql
     SELECT batch_id, COUNT(*), SUM(total_amount)
     FROM settlement.batches 
     WHERE created_at > NOW() - INTERVAL '2 hours'
     GROUP BY batch_id HAVING COUNT(*) > 1;
     ```
  2. Contact bank to reverse duplicate transfer
  3. If cannot reverse, book as settlement loss and escalate to CFO

- **If settlement cancelled while retrying:**
  1. Cancel pending retries:
     ```sql
     UPDATE settlement.batches
     SET status = 'CANCELLED', error_message = 'Manual cancel — incident {incident_id}'
     WHERE id = '<batch_id>' AND status = 'PENDING';
     ```
  2. Re-create batch with corrected parameters

## Communication Template

**Initial Alert:**
```
🔴 SETTLEMENT BATCH FAILED — P0
Time: {current_time} Syria Time
Batch ID: {batch_id}
Amount: {amount} SYP
Bank: {bank}
Error: {error_message}
Retry count: {retries}
Impact: {affected_users} merchants/agents pending settlement
Incident ID: {incident_id}
```

**Update (15 min):**
```
🟡 SETTLEMENT FAILURE UPDATE
Time: {current_time}
Status: {investigating / contacting bank / preparing retry / CBS RTGS down — waiting}
ETA: {estimated resolution}
Affected batches queued: {count} ({total_amount} SYP)
```

**Resolution:**
```
🟢 SETTLEMENT FAILURE RESOLVED
Time: {current_time}
Batch {batch_id} settled successfully at {completion_time}
Total outage: {duration}
Root cause: {reason — e.g., "Bank account insufficient balance — manual top-up required"}
Action items: {CFO to review auto-top-up trigger threshold}
```

**Arabic + English Stakeholder Message:**
```
English:
Settlement batch {batch_id} experienced a failure at {time} due to {reason}.
Issue resolved at {resolution_time}. All pending settlements processed.
Impact: {merchant_count} merchants, {agent_count} agents — payments now confirmed.
We apologise for any inconvenience.

Arabic:
تعذرت معالجة دفعة التسوية رقم {batch_id} في الساعة {time} بسبب {reason}.
تم حل المشكلة في الساعة {resolution_time}. تمت معالجة جميع المدفوعات العالقة.
عدد التجار المتأثرين: {merchant_count}، عدد الوكلاء المتأثرين: {agent_count} — تم تأكيد المدفوعات الآن.
نعتذر عن أي إزعاج.
```

## Post-Mortem

- **Root cause analysis:**
  - Was the failure bank-side, CBS RTGS-side, or Beza-side?
  - Was there a predictable trigger (e.g., balance threshold)?
  - Did monitoring catch it in time?
  - Was manual intervention required? How long did it take?

- **Data to collect:**
  - Batch processing logs (settlement-engine pods)
  - Bank API response logs
  - CBS RTGS heartbeat data
  - Treasury account balance history
  - Reconciliation match results for affected period

- **Teams to involve:**
  - Engineering (settlement engine reliability, retry logic)
  - Treasury (balance monitoring, auto top-up triggers)
  - Finance Operations (manual settlement procedures)
  - Compliance (CBS notification if settlement > 2h delayed)
  - Bank Relationship (bank-side issue follow-up)
  - Product (merchant/agent communication during outages)

- **Follow-up actions:**
  - Implement automated balance check before batch submission
  - Add Tier 1 → Tier 2 auto-sweep when balance below threshold
  - Set up CBS RTGS status monitoring (currently manual check)
  - Create settlement runbook drill schedule (monthly)
  - Review settlement SLA credits owed to merchants (if applicable)
