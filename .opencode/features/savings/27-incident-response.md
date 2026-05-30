# Savings Incident Response

## Incident Classification

| Severity | Definition | Response Time | Examples |
|----------|-----------|---------------|----------|
| **P0** | Critical — User funds at risk or system inoperable | < 15 min | Double auto-save debit, profit distribution failure, goal data loss |
| **P1** | High — Major feature broken, significant UX impact | < 1 hour | Cannot create goals, cannot withdraw, auto-save not executing |
| **P2** | Medium — Feature degraded, partial impact | < 4 hours | Round-up intermittent, profit history not loading, slow goal creation |
| **P3** | Low — Cosmetic, minor bugs | < 1 week | Progress bar animation glitch, wrong milestone message, Arabic text issues |

## Runbooks

### P0 Runbook: Double Auto-Save Debit
```
Symptom: Auto-save debited main wallet but did not credit goal (money lost)
         OR auto-save debited twice for same goal

Detection:
  1. Monitoring alert: Auto-save success rate < 95%
  2. User complaints: "تم خصم مبلغ من محفظتي ولكن لم يضف إلى هدف التوفير"

Immediate Actions:
  1. [ENG] Halt auto-save cron:
     php artisan schedule:list | grep savings
     php artisan down --message="Auto-save temporarily disabled"

  2. [ENG] Identify affected transactions:
     SELECT * FROM auto_save_logs
     WHERE executed_at > NOW() - INTERVAL 1 HOUR
     AND status = 'completed'
     AND reference NOT IN (
         SELECT reference FROM savings_transactions
         WHERE type = 'deposit' AND sub_type = 'auto_save'
     );

  3. [ENG] For each affected goal, validate:
     - CFE balance vs savings_goals.current_amount
     - wallet_transactions reference exists

  4. [OPS] If debit without credit:
     - Manually credit savings sub-wallet via CFE admin
     - Create savings_transaction record
     - Update goal.current_amount
     - Reference: "incident-recovery-{incident_id}"

  5. [OPS] If double debit:
     - Reverse one debit via CFE reversal
     - Reversal reference: "incident-reversal-{incident_id}"

  6. [ENG] Identify root cause:
     - Check queue: was job processed twice?
     - Check lock: auto-save lock expired?
     - Fix: strengthen withoutOverlapping, add idempotency check

Post-Mortem:
  - Document affected users and amounts
  - Communicate to affected users: apology + amounts restored
  - Implement preventive: idempotency_key unique constraint
  - Implement detection: reconciliation check every auto-save batch
```

### P1 Runbook: Profit Distribution Not Running
```
Symptom: 1st of month, 01:00 — profit not distributed
         Distribution count = 0 after scheduled time

Detection:
  1. Cron alert: "savings:calculate-profit failed"
  2. No ProfitDistributed events in last 24 hours
  3. Manual check: php artisan savings:calculate-profit --dry-run

Immediate Actions:
  1. [ENG] Check cron status:
     grep "savings:calculate-profit" storage/logs/savings-profit.log

  2. [ENG] Verify CFE pool return available:
     - Check CFE admin dashboard for pool return report
     - If CFE report missing: contact CFE operations

  3. [OPS] If CFE return available but distribution failed:
     php artisan savings:calculate-profit
     php artisan savings:distribute-profit

  4. [OPS] If partial distribution (some goals missed):
     php artisan savings:distribute-profit --retry-failed

  5. [ENG] If pool return is 0 or negative:
     - Skip distribution this month
     - Notify users: "لم تتحقق أرباح هذا الشهر — رأس المال محفوظ"
     - No profit = no management fee charged

Post-Mortem:
  - Why did cron fail? Server issue? CFE API timeout?
  - Fix: add retry logic, staggered retry (1h, 4h, 12h)
  - Fix: add manual trigger button in admin panel
```

### P1 Runbook: User Cannot Withdraw
```
Symptom: Withdrawal API returns 500 or timeout
         User reports: "لا أستطيع سحب أموالي من هدف التوفير"

Triage:
  1. Check if single user or all users affected
  2. Check recent deployments to savings module
  3. Check CFE API status (withdrawals go through CFE)

Actions:
  [Single user]
  1. Check goal status: DB → is it locked? is it active?
  2. Check user PIN: recent PIN change? lockout?
  3. Check balance: sufficient for withdrawal?
  4. If stuck transaction (pending hold): release hold on CFE

  [All users]
  1. Check CFE API: is it responding?
  2. Check queue: savings_high backlog?
  3. Check DB: connection pool, deadlocks?
  4. Rollback recent deployment if identified as cause

Workaround:
  - Manual withdrawal via admin panel (ops team)
  - CFE direct withdrawal + manual savings_transaction record
```

## Communication Templates

### User Notification (P0 Incident)
```json
{
  "push_title": "عذراً — تم حل المشكلة",
  "push_body": "تمت استعادة المبالغ المتأثرة بعطل فني. رصيد هدف التوفير محدث الآن.",
  "sms": "عذراً للخلل الفني الذي أثر على هدف التوفير الخاص بك. تمت استعادة جميع المبالغ. الرصيد الحالي: {amount} ل.س — Beza",
  "in_app_banner": "تم حل مشكلة فنية أثرت على التوفير. جميع المبالغ مستعادة."
}
```

### Internal Escalation
```
Subject: [P0] Savings Incident — {incident_summary}
Severity: CRITICAL
Time: {timestamp}
Affected: {count} users / {count} goals
Impact: {description}
Action: {taken}
Owner: {name}
Status: Investigating | Mitigated | Resolved

Slack: #incident-savings
Zoom: {incident_bridge_link}
```

## Post-Mortem Template
```markdown
## Savings Incident Post-Mortem

### Summary
- **Date**: 2026-MM-DD
- **Duration**: XX minutes
- **Severity**: P0/P1/P2
- **Impact**: X users, X goals, X SYP affected

### Timeline
- HH:MM — Detection (alert / user report)
- HH:MM — Investigation started
- HH:MM — Root cause identified
- HH:MM — Mitigation applied
- HH:MM — System fully restored

### Root Cause
{technical explanation}

### Resolution
{what was done to fix}

### Lessons Learned
1. {prevention #1}
2. {prevention #2}
3. {detection improvement}

### Action Items
- [ ] Add monitoring: {metric}
- [ ] Fix code: {file/line}
- [ ] Update runbook: {runbook_name}
- [ ] Test: {test_case}
```
