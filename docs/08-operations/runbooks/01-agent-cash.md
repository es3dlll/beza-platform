# Runbook: Agent Out of Cash

## Severity: P1

## Symptoms

- **Alerts:**
  - Grafana alert `agent_float_critical` fires when any premium agent float drops below 100,000 SYP
  - PagerDuty notification: "AGENT_FLOAT_CRITICAL — Agent ID {agent_id} float at {amount} SYP"
  - Slack #agent-alerts: multiple agents reporting float < 50,000 SYP
  - CBS non-compliance risk if > 10% of agents out of float simultaneously (per CBS agent network regulation 2024/08)

- **What users (agents) see:**
  - Agent mobile app displays: "رصيدك منخفض — يرجى التواصل مع الدعم" (Your balance is low — please contact support)
  - Agent cannot process cash-out transactions > 50,000 SYP
  - Customer sees error: "الوكيل غير متاح حالياً" (Agent temporarily unavailable) when trying cash-out

- **What dashboards show:**
  - `https://grafana.beza-sy.com/d/agent-float` — Agent float gauge showing red for affected agents
  - `https://grafana.beza-sy.com/d/agent-float-geo` — Syria heatmap showing float gaps by governorate (Damascus, Aleppo, Homs, Latakia, Tartous, etc.)
  - `https://metabase.beza-sy.com/dashboard/agent-topup` — Pending top-up requests queue

## Immediate Actions (First 5 min)

1. **Acknowledge the alert in PagerDuty:**
   ```
   pd acknowledge -i <incident_id>
   ```

2. **Identify affected agents and their float levels:**
   ```sql
   SELECT a.id, a.name, a.governorate, af.current_float, af.last_top_up_at
   FROM agent.agents a
   JOIN agent.float_balances af ON a.id = af.agent_id
   WHERE af.current_float < 100000
   ORDER BY af.current_float ASC;
   ```

3. **Check if this is a systemic issue (multiple agents) or isolated:**
   ```
   curl -s https://grafana.beza-sy.com/api/dashboards/uid/agent-float | jq '.dashboard.panels[] | select(.title=="Float by Governorate")'
   ```

4. **Check Treasury available Tier 1 cash position:**
   ```sql
   SELECT account_name, balance FROM treasury.bank_accounts
   WHERE account_type = 'TIER1_CASH' AND bank = 'CBS';
   ```

5. **Check pending top-up requests that may be stuck:**
   ```sql
   SELECT * FROM agent.top_up_requests
   WHERE status = 'PENDING' AND created_at > NOW() - INTERVAL '2 hours';
   ```

## Investigation Steps

1. **Open the Agent Float dashboard:**
   - URL: `https://grafana.beza-sy.com/d/agent-float`
   - Look for float trend lines — are agents gradually depleting or sudden drop?
   - Filter by governorate to identify geographic concentration

2. **Check the CBS RTGS status (if interbank transfers are delayed):**
   - URL: `https://srm.cbs.gov.sy/rtgs/status`
   - Verify settlement window is open (09:00–14:00 Syria time)
   - If RTGS down, immediate escalation to CBS operations desk: +963 11 245 890

3. **Run the agent float projection query:**
   ```sql
   SELECT
       a.governorate,
       COUNT(*) as agents_at_risk,
       SUM(af.current_float) as total_remaining_float,
       SUM(af.daily_avg_cash_out * 1.2) as expected_daily_need,
       CASE
           WHEN SUM(af.current_float) < SUM(af.daily_avg_cash_out * 1.2)
           THEN 'CRITICAL' ELSE 'WARNING'
       END as status
   FROM agent.agents a
   JOIN agent.float_balances af ON a.id = af.agent_id
   GROUP BY a.governorate
   ORDER BY agents_at_risk DESC;
   ```

4. **Check last successful top-up batch:**
   ```
   curl -s https://beza-api.internal/top-ups/latest-batch | jq
   ```

5. **Verify Cash Transport Syria logistics (for physical cash delivery):**
   - Contact: CTS Operations: +963 11 233 4455
   - Check if any scheduled deliveries were missed today

## Resolution Steps

1. **Process emergency top-up transfers from Tier 2 (Bank) to affected agents:**
   ```sql
   BEGIN;
   UPDATE treasury.bank_accounts
   SET balance = balance - <total_topup_amount>
   WHERE account_name = 'BSO_AGENT_FUNDING' AND balance >= <total_topup_amount>;
   
   INSERT INTO agent.top_up_batch (total_amount, agent_count, initiated_by, status)
   VALUES (<total_topup_amount>, <agent_count>, 'incident-<id>', 'PROCESSING');
   
   -- Execute bank transfers via CBS RTGS or bank portal
   COMMIT;
   ```

   - Transfer amounts: Premium agents get 3M SYP, Standard get 1M SYP, Basic get 300K SYP
   - Use BSO bank portal `https://ebanking.bso-sy.com` for transfers ≤ 50M SYP
   - For > 50M SYP, use CBS RTGS with dual authorization

2. **Trigger bulk top-up via agent API:**
   ```
   curl -X POST https://beza-api.internal/top-ups/bulk \
     -H "Authorization: Bearer <treasury-token>" \
     -H "Content-Type: application/json" \
     -d '{
       "agent_ids": ["<agent_ids>"],
       "amounts": "<amounts>",
       "source_account": "BSO_AGENT_FUNDING",
       "reason": "Incident <incident_id> — emergency float top-up",
       "approver": "<your_name>"
     }'
   ```

3. **Send in-app notification to affected agents:**
   ```
   curl -X POST https://beza-api.internal/notifications/agent-broadcast \
     -H "Authorization: Bearer <ops-token>" \
     -d '{
       "type": "FLOAT_TOPUP_CONFIRMED",
       "agent_ids": ["<agent_ids>"],
       "message_ar": "تم تعبئة رصيدك. يمكنك الآن تقديم الخدمات كالمعتاد. شكراً لصبرك."
     }'
   ```

4. **Verify float restored:**
   ```sql
   SELECT id, name, current_float, last_top_up_at
   FROM agent.float_balances
   WHERE agent_id IN (<affected_ids>);
   ```

5. **Update CBS compliance report if agents were out > 2 hours:**
   - Log incident in CBS compliance portal: `https://srm.cbs.gov.sy/compliance/report`
   - File agent network disruption report (required if > 5% of agents affected)

## Rollback Plan

- **If top-up was processed in error (wrong amount or wrong agent):**
  1. Immediate request for float recall from agent via mobile app notification
  2. If agent uncooperative, freeze agent account via:
     ```sql
     UPDATE agent.agents SET status = 'FROZEN' WHERE id = <agent_id>;
     ```
  3. Initiate chargeback from agent wallet to treasury account
  4. Report to compliance for fraud investigation if amount > 500K SYP

- **If treasury transfer to bank was authorised in error:**
  1. Contact BSO/Bemo immediately to reverse the transfer (before 14:00 cutoff)
  2. If after cutoff, book reversal for next business day
  3. Log in treasury audit trail

- **If bulk top-up API double-sent funds:**
  1. Run SQL to identify duplicate top-ups within same minute:
     ```sql
     SELECT agent_id, COUNT(*), SUM(amount)
     FROM agent.top_up_requests
     WHERE created_at > NOW() - INTERVAL '10 minutes'
     GROUP BY agent_id HAVING COUNT(*) > 1;
     ```
  2. Issue recall for duplicates > 1M SYP total per agent
  3. Adjust float balance via admin tool

## Communication Template

**Initial Alert (immediate):**
```
🔴 AGENT FLOAT CRITICAL
Time: {current_time} Syria Time
Affected agents: {count} agents across {governorates}
Total shortfall: {amount} SYP
Severity: P1
Incident ID: {incident_id}
Action: Treasury initiating emergency top-ups from BSO account. Estimated resolution: 30 min.
```

**Update (15 min):**
```
🟡 AGENT FLOAT INCIDENT UPDATE
Time: {current_time}
Status: Bulk top-up in progress
{EOS} agents topped up, {remaining} remaining (~{amount} SYP)
Next update: 15 min
```

**Resolution:**
```
🟢 AGENT FLOAT INCIDENT RESOLVED
Time: {current_time}
All agents topped up. Total disbursed: {total} SYP
Root cause: {reason — e.g., "CBS RTGS delay caused bank transfer backlog"}
Post-mortem scheduled: {date}
```

**Arabic Stakeholder Message:**
```
عذراً منكم، تم رصد انخفاض في أرصدة الوكلاء في محافظات {governorates}.
تم البدء بعملية تعبئة عاجلة للرصيد عبر التحويل المصرفي.
نعتذر عن أي إزعاج، وسيتم حل المشكلة خلال 30 دقيقة.
يرجى متابعة تحديثات الحالة.
```

## Post-Mortem

- **Root cause analysis required:**
  - Was this predictable? Check forecast vs actual agent cash-out for last 7 days
  - Was the top-up process initiated manually or was automated threshold breached?
  - Did CBS RTGS contribute to delay?
  - Review agent float tier thresholds — are they still appropriate given current volumes?

- **Data to collect:**
  - Grafana dashboard snapshot at time of incident
  - Agent float trend for 7 days preceding incident
  - Top-up batch logs and bank transfer confirmations
  - CBS RTGS status logs
  - All Slack/PagerDuty communications

- **Teams to involve:**
  - Treasury (cash forecasting accuracy)
  - Agent Operations (float threshold review)
  - Engineering (top-up API reliability)
  - Compliance (CBS notification requirement)
  - Data (forecast model drift analysis)

- **Follow-up actions:**
  - Adjust agent float tier minimums if needed
  - Implement automated top-up trigger at 20% float remaining (currently 10%)
  - Add governorate-level float buffers for high-volume regions (Damascus 20% buffer)
  - Schedule weekly float forecasting review with Treasury
