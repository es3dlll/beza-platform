# Agent Network Incident Response

## Incident Severity Levels

| Level | Definition | Response Time | Examples |
|-------|-----------|---------------|----------|
| P0 | Critical — Network-wide outage affecting all agents | 15 min, 24/7 | Agent network completely down, all cash-in/out failing |
| P1 | High — Major feature broken for many agents | 30 min, business hours | Float discrepancies, commission settlement failed, POS app crash on launch |
| P2 | Medium — Feature broken for some agents | 2 hours, business hours | Agent registration failing, SMS not sending, receipt printing broken |
| P3 | Low — Minor issue, workaround available | 1 business day | Cosmetic UI issue, slow transaction history load, typo in Arabic text |

## P0: Agent Network Outage

### Definition
- All cash-in and cash-out transactions failing for >5 minutes
- All agents unable to login
- 100% error rate on POST /agent/cash-in and POST /agent/cash-out
- API gateway returning 503 for all agent endpoints

### Runbook

```
Step 1: Acknowledge (within 5 min)
  - PagerDuty alert triggered → on-call engineer acknowledges
  - Post in Slack #ops-critical: "🔴 P0 Agent Network Outage — investigating"
  - Check if related to recent deployment

Step 2: Initial Diagnosis (within 10 min)
  - Check Grafana dashboard:
    - API error rate > 5%? → Check NLB target group health
    - Database connections exhausted? → Check RDS connections
    - Redis unreachable? → Check ElastiCache
  - Check recent deployments in last hour → git log --oneline -10
  - Check cloud provider status page (AWS Service Health)

Step 3: Common Scenarios & Mitigation

  Scenario A: API service down (pods crash-looping)
    Check: kubectl get pods -n agent-pos
    Fix: kubectl rollout restart deployment/agent-pos-api
    If still failing: kubectl rollout undo deployment/agent-pos-api
    If still failing: Check resource limits (OOMKilled?)
    Escalate: Check application logs → kubectl logs -n agent-pos pod/agent-pos-api-xxx

  Scenario B: Database connectivity lost
    Check: MySQL connection pool exhausted
    Fix: Kill long-running queries
      SHOW FULL PROCESSLIST;
      KILL QUERY {process_id};
    Fix: Increase max_connections temporarily
      SET GLOBAL max_connections = 500;
    Long-term: Add read replica or optimize slow queries

  Scenario C: Redis down (cache + queue)
    Check: redis-cli ping
    Fix: Restart Redis
      kubectl rollout restart statefulset/redis
    Impact: Float cache stale, queue jobs delayed but NOT critical (DB will handle)
    Commission settlement delayed but transactions still work

  Scenario D: SSL certificate expired
    Check: openssl s_client -connect api.beza.com:443
    Fix: Deploy new certificate from CA
    Impact: All mTLS connections fail → total outage

Step 4: Communicate (ongoing)
  - Every 15 min: update in Slack #ops-critical
  - If >30 min: email stakeholders (ops@beza.com)
  - If >1 hour: notify CEO via WhatsApp

Step 5: Resolve
  - Confirm fix: Run test cash-in/cash-out on staging → production
  - Verify: Grafana metrics returning to normal
  - Post: "✅ P0 resolved at HH:MM. Root cause: {summary}. Post-mortem scheduled."

Step 6: Post-Mortem (within 48 hours)
  - Timeline of events
  - Root cause analysis (5 Whys)
  - Action items to prevent recurrence
  - Assign owners and due dates
```

## P1: Float Discrepancy Between Agent and System

### Definition
- Agent float_balance in DB does not match calculated float from transaction history
- Discrepancy > 5,000 SYP for a single agent
- Detected by EOD reconciliation or agent report

### Runbook

```
Step 1: Triage
  - Check severity: single agent or multiple agents?
  - If single: P1 (this runbook)
  - If >10 agents: escalate to P0 (possible system bug)

Step 2: Investigate
  - Query discrepancy details:
    SELECT agent_id, float_balance, 
      (SELECT SUM(CASE WHEN type IN ('cash_out','float_funding','float_transfer_in','commission')
                       THEN amount ELSE -amount END)
       FROM agent_transactions WHERE agent_id = {agent_id} AND created_at < NOW()) as calculated
    FROM agents WHERE id = {agent_id};

  - Check for orphan transactions (synced but not applied):
    SELECT * FROM agent_transactions 
    WHERE agent_id = {agent_id} AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND status = 'completed' ORDER BY created_at DESC;

  - Check for pending offline transactions:
    SELECT * FROM offline_sync_log 
    WHERE agent_id = {agent_id} AND status = 'pending';

  - Check for duplicate idempotency keys:
    SELECT idempotency_key, COUNT(*) as cnt 
    FROM agent_transactions WHERE agent_id = {agent_id}
    GROUP BY idempotency_key HAVING cnt > 1;

Step 3: Common Causes & Resolution

  Cause 1: Duplicate offline sync transaction (90% of cases)
    Fix: Reverse the duplicate transaction
      INSERT INTO agent_transactions (agent_id, type, amount, ...)
      VALUES ({agent_id}, 'reversal', -{duplicate_amount}, 'تعديل معاملة مكررة');
      UPDATE agents SET float_balance = float_balance - {duplicate_amount}
      WHERE id = {agent_id};
    Notify agent: "تم تعديل رصيد صندوقك بسبب معاملة مكررة"

  Cause 2: Agent mis-keyed amount (5%)
    Example: Agent entered 1,000,000 instead of 100,000
    Fix: IF difference < 24h and customer hasn't disputed:
      Reverse original + Create correct transaction
      Notify both agent and customer
    IF > 24h:
      Create adjustment transaction
      Notify agent of correction

  Cause 3: System calculation bug (3%)
    Fix: Deploy hotfix
    Post: Manual adjustment for affected agents
    Post-mortem: Engineering review

  Cause 4: Fraud (2%)
    Fix: Immediately suspend agent
    Notify compliance team
    File SAR if amount > 1M SYP
    Legal action if warranted

Step 4: Resolution
  - Adjust float_balance in DB (with audit trail)
  - Ensure agent is notified
  - Update reconciliation report
  - Close incident in tracking system
```

## P2: Commission Calculation Error

### Definition
- Batch commission settlement failed or produced incorrect amounts
- Individual commission calculated incorrectly (wrong rate applied)
- Detected by automated reconciliation or agent complaint

### Runbook

```
Step 1: Triage
  - Check commission settlement batch status:
    SELECT * FROM agent_commission_settlements 
    WHERE status != 'completed' ORDER BY created_at DESC LIMIT 5;
  - Check error log in settlement batch

Step 2: Investigate

  Scenario A: Batch failed to run
    Check: artisan agent:settle-commissions
    Check: Queue worker status (php artisan horizon:status)
    Fix: Restart queue worker
      php artisan horizon:terminate && php artisan horizon
    Retry: php artisan agent:settle-commissions --force

  Scenario B: Wrong commission rate applied
    Check: Commission rate in agent_tier_config vs rate_applied in agent_commissions
    SELECT ac.*, atc.commission_rate_cash_in 
    FROM agent_commissions ac
    JOIN agent_tier_config atc ON ac.agent_id = ...;
    
    If mismatch:
      - Calculate correct commission
      - Create adjustment entry
      - Schedule correction in next settlement batch

  Scenario C: Double commission recorded
    Check: agent_transactions with multiple commission entries for same txn_id
    SELECT transaction_id, COUNT(*) as cnt 
    FROM agent_commissions GROUP BY transaction_id HAVING cnt > 1;
    
    Fix: Delete duplicate commission entries
    Update pending_commission accordingly

Step 3: Resolution
  - If batch partially failed: re-run for affected agents only
  - If wrong rates: calculate and apply corrections
  - Notify affected agents with apology
  - If financial impact > 50,000 SYP for any agent: expedite correction
  - Log as bug in engineering backlog

Step 4: Prevention
  - Add validation: settlement batch should reconcile before execution
  - Add monitoring for commission rate changes
  - Add test coverage for rate calculation edge cases
```

## P2: Agent POS App Crash on Launch

### Runbook

```
Step 1: Triage
  - Crashlytics/Firebase dashboard → check crash rate
  - Isolate: specific Android version? Specific device model? Specific app version?
  - Number of affected agents

Step 2: Common Causes

  Cause 1: Bad app update (recent release)
    Mitigation: Roll back to previous version via MDM
    Fix: Deploy hotfix with crash fix
    Workaround: Clear app cache/data via MDM remote command

  Cause 2: Storage full on device
    Check: Device storage metrics from MDM
    Fix: Remote clear cache via MDM
    Tell agent: "احذف بعض الملفات من جهازك"

  Cause 3: Android OS update incompatible
    Check: Crashlytics shows all on same Android version
    Mitigation: Block OS updates via MDM
    Fix: Update app for compatibility

Step 3: Resolution
  - Push hotfix or rollback via MDM
  - Verify fix on staging device
  - Re-enable app for agents
  - Track: crash rate back to <0.1%
```

## Incident Communication Templates

### Slack #ops-critical Message
```
🔴 P0/P1: {incident_title}
━━━━━━━━━━━━━━━━━━━━━━━━━━
Status: {investigating | mitigating | resolved}
Impact: {number} agents affected
Started: {time}
Updates:
  • {time}: {action taken}
  • {time}: {new finding}
Next update: {time}
Lead: @engineer_name
```

### Agent-Facing Message (SMS/Push)
```
P0/P1: عذراً من توقف الخدمة. فريقنا يعمل على حل المشكلة.
سيتم إعلامكم فور عودة الخدمة. للاستفسار: +963800123456
```
