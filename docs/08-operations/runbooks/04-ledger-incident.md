# Runbook: Ledger Imbalance

## Severity: P0

## Symptoms

- **Alerts:**
  - Grafana alert `ledger_imbalance` fires when `SUM(debits) - SUM(credits) != 0` across the general ledger
  - PagerDuty: "LEDGER_IMBALANCE — Account {account_code} — imbalance {amount} SYP — {type: drift/break}"
  - Slack #ledger-alerts: "🚨 Ledger imbalance detected! Drift: {amount} SYP — {description}"
  - Reconciliation engine alert: `recon_internal_match_rate` drops below 90%
  - CBS mandatory reporting trigger if imbalance > 1M SYP outstanding > 1 hour
  - Datadog metric `ledger.imbalance_amount` non-zero

- **What users see:**
  - Customers: wallet balances may be wrong (too high/too low) — "رصيدي ناقص" / "رصيدي زائد" complaints spike
  - Agents: commission calculations appear incorrect
  - Merchants: settlement amounts don't match expected payout
  - Finance team: trial balance won't reconcile — "قيد المراجعة" (Under review) banner on all financial reports
  - Internal operations: transfers may be stuck in "PENDING" if ledger validation fails

- **What dashboards show:**
  - `https://grafana.beza-sy.com/d/ledger` — Ledger balance panel shows RED non-zero imbalance
  - `https://grafana.beza-sy.com/d/ledger-drilldown` — Account-level drift heatmap highlights affected accounts
  - `https://metabase.beza-sy.com/dashboard/ledger-audit` — Real-time trial balance shows unbalanced entries

## Immediate Actions (First 5 min)

1. **Acknowledge the alert:**
   ```
   pd acknowledge -i <incident_id>
   ```

2. **Freeze affected financial operations (prevent making imbalance worse):**
   ```sql
   UPDATE system.service_flags SET enabled = false 
   WHERE service IN ('WALLET_TRANSFER', 'FX_CONVERSION', 'SETTLEMENT_SUBMIT');
   ```
   - This pauses outbound transfers while investigations proceed
   - Keep inbound transactions running (money-in is easier to track)

3. **Identify the size and scope of the imbalance:**
   ```sql
   -- Check overall ledger balance
   SELECT 
       SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE 0 END) as total_debits,
       SUM(CASE WHEN entry_type = 'CREDIT' THEN amount ELSE 0 END) as total_credits,
       SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE -amount END) as imbalance,
       COUNT(*) as affected_entries
   FROM ledger.entries
   WHERE created_at > NOW() - INTERVAL '1 hour'
     AND status = 'POSTED';
   ```

4. **Run the trial balance check to find which accounts are out:**
   ```sql
   SELECT 
       account_code,
       account_name,
       SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE 0 END) as debits,
       SUM(CASE WHEN entry_type = 'CREDIT' THEN amount ELSE 0 END) as credits,
       SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE -amount END) as balance,
       ABS(SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE -amount END)) as abs_imbalance
   FROM ledger.entries e
   JOIN ledger.accounts a ON e.account_code = a.code
   WHERE e.created_at > NOW() - INTERVAL '1 hour'
     AND e.status = 'POSTED'
   GROUP BY account_code, account_name
   HAVING ABS(SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE -amount END)) > 0
   ORDER BY abs_imbalance DESC;
   ```

5. **Check if this is a drift (cumulative small error) or break (single large error):**
   ```sql
   -- Count entries that contributed to the imbalance
   SELECT 
       CASE WHEN COUNT(*) = 1 THEN 'BREAK (single entry)'
            WHEN COUNT(*) < 10 THEN 'Minor Drift'
            ELSE 'Major Drift'
       END as imbalance_type,
       COUNT(*) as entry_count,
       SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE -amount END) as total_drift
   FROM ledger.entries
   WHERE created_at > NOW() - INTERVAL '1 hour'
     AND status = 'POSTED';
   ```

## Investigation Steps

1. **Open ledger monitoring dashboard:**
   - URL: `https://grafana.beza-sy.com/d/ledger`
   - Check the "Account-Level Imbalance" panel for specific accounts in red
   - Check the "Time-Series Drift" panel to see when imbalance first appeared
   - URL: `https://grafana.beza-sy.com/d/ledger-drilldown`

2. **Check recent DB migration or deployment that may have caused it:**
   ```
   kubectl rollout history -n ledger-api deployment/ledger-service
   kubectl logs -n ledger-api -l app=ledger-service --tail 50 --since=2h | grep -i error
   ```

3. **Check for orphaned entries (debit without credit, or vice versa):**
   ```sql
   SELECT e.* 
   FROM ledger.entries e
   LEFT JOIN ledger.entry_pairs ep ON e.id = ep.entry_a_id OR e.id = ep.entry_b_id
   WHERE ep.id IS NULL 
     AND e.created_at > NOW() - INTERVAL '2 hours'
     AND e.status = 'POSTED';
   ```

4. **Check for duplicate entries (exact same transaction posted twice):**
   ```sql
   SELECT transaction_ref, COUNT(*), SUM(amount)
   FROM ledger.entries
   WHERE created_at > NOW() - INTERVAL '2 hours'
   GROUP BY transaction_ref
   HAVING COUNT(*) > 2;
   ```

5. **Check Kafka consumer lag (entries may not have been consumed properly):**
   ```
   kafka-consumer-groups --bootstrap-server kafka.beza-sy.internal:9092 \
     --group ledger-consumer \
     --describe
   ```

6. **Check the reconciliation engine for CFE posting discrepancies:**
   ```sql
   SELECT * FROM recon.match_results
   WHERE created_at > NOW() - INTERVAL '2 hours'
     AND match_type = 'FAILED'
     AND mismatch_reason LIKE '%ledger%';
   ```

7. **Cross-reference with CBS reporting data:**
   ```
   curl -s -H "Authorization: Bearer <cbs-srm-token>" \
     https://srm.cbs.gov.sy/api/v1/compliance/daily-report | jq '.ledger_balance'
   ```

## Resolution Steps

1. **If single-entry break (one transaction caused imbalance):**
   - Identify the erroneous entry:
     ```sql
     SELECT e.*, et.source, et.source_id 
     FROM ledger.entries e
     JOIN ledger.entry_sources et ON e.id = et.entry_id
     WHERE e.created_at > NOW() - INTERVAL '2 hours'
       AND e.status = 'POSTED'
     ORDER BY e.created_at DESC LIMIT 1;
     ```
   - If it's a duplicate, void the duplicate:
     ```sql
     UPDATE ledger.entries 
     SET status = 'VOID', void_reason = 'Duplicate — incident {incident_id}', voided_by = '<your_name>'
     WHERE id = '<duplicate_entry_id>';
     ```
   - If it's a wrong amount, post a correcting entry:
     ```sql
     -- Correcting journal entry
     INSERT INTO ledger.entries (account_code, entry_type, amount, currency, description, source, status)
     VALUES 
       ('<correcting_account>', 'DEBIT', <correction_amount>, 'SYP', 
        'Correction for entry {original_id} — incident {incident_id}', 'MANUAL', 'POSTED'),
       ('<offset_account>', 'CREDIT', <correction_amount>, 'SYP',
        'Correction offset for entry {original_id} — incident {incident_id}', 'MANUAL', 'POSTED');
     ```

2. **If cumulative drift (many small errors):**
   - Run the automated drift correction job:
     ```
     kubectl -n ledger-api create job --from=cronjob/ledger-drift-corrector manual-drift-fix-{incident_id}
     ```
   - Or manually compute and post batch correction:
     ```sql
     -- Calculate net drift per account and post batch correcting entry
     WITH drift AS (
       SELECT account_code, 
              SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE -amount END) as net_drift
       FROM ledger.entries
       WHERE created_at > NOW() - INTERVAL '2 hours' AND status = 'POSTED'
       GROUP BY account_code
       HAVING ABS(SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE -amount END)) > 0
     )
     INSERT INTO ledger.entries (account_code, entry_type, amount, currency, description, source, status)
     SELECT 
       account_code,
       CASE WHEN net_drift > 0 THEN 'CREDIT' ELSE 'DEBIT' END,
       ABS(net_drift),
       'SYP',
       'Drift correction — incident {incident_id}',
       'DRIFT_CORRECTION',
       'POSTED'
     FROM drift;
     ```

3. **If Kafka consumer lag caused missed entries:**
   - Reset the consumer offset to replay missed messages:
     ```
     kafka-consumer-groups --bootstrap-server kafka.beza-sy.internal:9092 \
       --group ledger-consumer \
       --reset-offsets --to-earliest \
       --topic ledger.entries \
       --execute
     ```
   - Or trigger a re-processing job:
     ```
     kubectl -n ledger-api create job --from=cronjob/ledger-reprocess manual-reprocess-{incident_id}
     ```

4. **Verify imbalance resolved:**
   ```sql
   SELECT 
       SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE 0 END) as total_debits,
       SUM(CASE WHEN entry_type = 'CREDIT' THEN amount ELSE 0 END) as total_credits,
       SUM(CASE WHEN entry_type = 'DEBIT' THEN amount ELSE -amount END) as imbalance
   FROM ledger.entries
   WHERE status = 'POSTED';
   ```
   - Check dashboard returns to zero: `https://grafana.beza-sy.com/d/ledger`

5. **Re-enable financial operations:**
   ```sql
   UPDATE system.service_flags SET enabled = true 
   WHERE service IN ('WALLET_TRANSFER', 'FX_CONVERSION', 'SETTLEMENT_SUBMIT');
   ```

6. **Notify CBS if imbalance exceeded 1M SYP for > 1 hour:**
   - File CBS incident report via SRM portal
   - Include corrective entries summary
   - Confirm no customer funds were lost

## Rollback Plan

- **If corrective entry was wrong:**
  1. Identify the erroneous correction:
     ```sql
     SELECT * FROM ledger.entries 
     WHERE description LIKE '%incident {incident_id}%' AND status = 'POSTED';
     ```
  2. Post reversing entry:
     ```sql
     INSERT INTO ledger.entries (account_code, entry_type, amount, currency, description, source, status)
     VALUES 
       ('<original_account>', '<opposite_type>', <amount>, 'SYP',
        'Reversal of erroneous correction — incident {incident_id}', 'MANUAL', 'POSTED');
     ```
  3. Re-run imbalance check

- **If drift correction over-corrected:**
  1. Re-run the drift query to identify new imbalance
  2. Post second correction for the delta
  3. Add validation step to drift correction procedure to prevent recurrence

- **If service flags were disabled affecting customer transactions:**
  1. Re-enable immediately upon imbalance resolution
  2. Review any failed transactions during freeze window
  3. Notify affected customers if relevant

## Communication Template

**Initial Alert:**
```
🔴 LEDGER IMBALANCE — P0
Time: {current_time} Syria Time
Imbalance amount: {amount} SYP
Account(s) affected: {accounts}
Type: {Drift / Break}
Potential root cause: {description}
Impact: Financial operations paused. Customer balances under review.
Incident ID: {incident_id}
```

**Update (15 min):**
```
🟡 LEDGER IMBALANCE UPDATE
Time: {current_time}
Status: {investigating / corrective entry posted / verifying}
Current imbalance: {current_amount} SYP (was {original_amount})
Resolution ETA: {estimated_time}
```

**Resolution:**
```
🟢 LEDGER IMBALANCE RESOLVED
Time: {current_time}
Final imbalance: 0 SYP
Root cause: {reason — e.g., "Duplicate Kafka message caused double-posting of wallet transfer"}
Corrective action: {description of fix}
Customer impact: {number} customers had incorrect balances — correcting entries posted automatically
```

**Arabic + English Stakeholder Message:**
```
English:
A ledger imbalance of {amount} SYP was detected at {time} affecting account(s) {accounts}.
The issue has been resolved. Correcting entries have been posted. All customer balances are accurate.
No funds were lost. Financial services are now fully operational.
We apologise for any inconvenience.

Arabic:
تم رصد خلل في ميزان الحسابات بمبلغ {amount} ل.س في الساعة {time} في الحسابات {accounts}.
تم حل المشكلة بالكامل. تم تسجيل قيود تصحيحية. جميع أرصدة العملاء صحيحة.
لا يوجد أي فقدان للأموال. جميع الخدمات المالية تعمل الآن بشكل طبيعي.
نعتذر عن أي إزعاج.
```

## Post-Mortem

- **Root cause analysis:**
  - Was this a code bug, data issue, or infrastructure failure?
  - Was it detected by monitoring within acceptable time?
  - Did the freeze and correction process work as expected?
  - Could this have been prevented by a transaction validation check?

- **Data to collect:**
  - Full ledger entry log for 6h preceding incident
  - Kafka consumer lag metrics
  - Recent deployment/change records
  - Reconciliation engine match results
  - CBS compliance report snapshot
  - All monitoring dashboard screenshots
  - Slack/PagerDuty communications

- **Teams to involve:**
  - Engineering (ledger service code review, Kafka consumer fix)
  - Finance Operations (corrective entry audit, customer impact assessment)
  - Compliance (CBS incident report, regulatory filing)
  - Data (monitoring and alert tuning)
  - QA (test coverage for ledger double-posting scenarios)
  - Product (customer communication review)

- **Follow-up actions:**
  - Add transaction idempotency key check to prevent duplicate posting
  - Implement automatic ledger balance watchdog (every 60s check)
  - Add deployment gate — require ledger balance check after every deploy
  - Schedule ledger reconciliation drill with Finance team (monthly)
  - Review and harden ledger write-path error handling
