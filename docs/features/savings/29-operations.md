# Savings Operations

## Operational Runbooks

### Daily Operations
```
06:00 — Check auto-save success rate (previous day)
        → Dashboard: Savings > Auto-Save Health
        → If < 98%: investigate skipped/failed logs

08:00 — Check round-up execution rate (previous day)
        → Dashboard: Savings > Round-Up Health
        → If < 97%: investigate failed round-ups

10:00 — Verify goal creation/deletion volumes
        → Dashboard: Savings > Goal Creation
        → Anomaly: > 3σ from 7-day rolling average

12:00 — Balance reconciliation check
        → Run: php artisan savings:reconcile
        → If discrepancy > 0.1%: escalate to engineering

End of Day — Review support tickets tagged "savings"
        → Respond to all open tickets
        → Escalate unresolved to engineering
```

### Weekly Operations
```
Monday 09:00 — Review auto-save adoption metrics
        → Are users enabling auto-save?
        → If adoption declining > 5%: investigate

Monday 10:00 — Review round-up adoption
        → Round-up toggle rate
        → Average round-up amount
        → User feedback on round-up

Wednesday — Savings feature health check
        → Run full test suite: php artisan test --testsuite=Savings
        → Check all cron jobs ran successfully
        → Verify queue depths

Friday — Team goal usage review
        → Teams created this week
        → Team completion rate
        → Average team size
```

### Monthly Operations (1st of Month)
```
00:00 — Profit distribution (automated)
        → Monitor: distribution jobs
        → Verify: total distributed = expected
        → If failure: see runbook 27-P1-profit-distribution

01:00 — Reconciliation
        → Full balance reconciliation with CFE
        → Export: savings_reconciliation_{YYYY-MM}.csv

02:00 — Monthly report generation
        → Regulatory compliance report
        → Sharia compliance report
        → Internal finance report

03:00 — Data cleanup
        → Archive auto_save_logs > 90 days
        → Archive round_up_executions > 90 days
        → Mark stale invite codes as expired

08:00 — Send monthly savings digest to users
        → "ملخص توفير شهر مايو: وفرت 150,000 ل.س، أرباح 3,500 ل.س"
```

## Admin Panel

### Savings Admin Features
```
1. Goal Management
   - View all goals (search by user, status, type)
   - Force-complete a goal
   - Force-cancel a goal (with refund)
   - Adjust goal.current_amount (audit-logged)
   - View goal transactions

2. Auto-Save Management
   - View auto-save logs (success/skip/fail)
   - Trigger manual auto-save for a user
   - Pause auto-save globally (maintenance mode)

3. Round-Up Management
   - View round-up execution history
   - Pause round-up globally
   - Adjust daily/monthly limits per user

4. Profit Distribution
   - Trigger dry-run: see expected distribution
   - Trigger actual distribution
   - View distribution history
   - Override distribution (audit-logged)

5. Team Management
   - View all teams
   - Disband team (with member refunds)
   - Regenerate invite code
   - View member contributions

6. Reports
   - Export savings report (CSV/PDF)
   - Export tax/compliance report
   - Export user statements
```

### Admin API Endpoints
```http
# Internal admin endpoints (staff-only, IP-restricted)

GET    /internal/admin/savings/goals?user_id=&status=&page=
GET    /internal/admin/savings/goals/{id}
POST   /internal/admin/savings/goals/{id}/adjust   # Adjust amount (with reason)
POST   /internal/admin/savings/goals/{id}/force-complete
POST   /internal/admin/savings/goals/{id}/force-cancel

GET    /internal/admin/savings/autosave/logs?date=
POST   /internal/admin/savings/autosave/trigger   # Manual trigger for user

GET    /internal/admin/savings/roundup/logs?date=
POST   /internal/admin/savings/roundup/toggle-global   # Pause/resume all

POST   /internal/admin/savings/profit/dry-run
POST   /internal/admin/savings/profit/distribute
GET    /internal/admin/savings/profit/history?period=

GET    /internal/admin/savings/teams
POST   /internal/admin/savings/teams/{id}/disband

GET    /internal/admin/savings/reports/daily
GET    /internal/admin/savings/reports/monthly
GET    /internal/admin/savings/reports/compliance
```

## Backup & Recovery

### Database Backup Schedule
```
Full backup: Daily at 01:00 (savings tables + CFE reference data)
  → Retention: 30 days on disk, 90 days in cold storage

Incremental: Every 6 hours (binary log)
  → Retention: 7 days

Transaction log: Real-time (CFE sync)
  → Savings transactions are immutable (insert-only)
  → Recovery: replay missing transactions from CFE logs
```

### Recovery Scenarios
```
Scenario 1: Single goal data corruption
  → Restore goal from last backup
  → Replay transactions from CFE logs (reference match)
  → Verify: goal.current_amount = CFE balance

Scenario 2: Bulk goal data loss (table corruption)
  → Restore from last full backup
  → Replay all transactions since backup from CFE
  → Verify: total CFE balance = SUM(goals.current_amount)

Scenario 3: CFE sub-account data loss
  → This is a P0 incident
  → CFE maintains own backups
  → Restore from CFE backup
  → Reconcile with savings_goals table

Scenario 4: Profit distribution rollback
  → Only possible if detected within 24 hours
  → Reverse all profit credits via CFE
  → Mark profit_distributions as reversed
  → Re-run distribution after fix
```

## Business Continuity

### Failover Procedures
```
Auto-Save Failover:
  Primary: Cron job on app server
  Failover: Secondary cron on standby server (same schedule)
  Manual: Admin panel trigger (ops team)

Profit Distribution Failover:
  Primary: Scheduled command
  Failover: Manual execution via admin panel
  Manual: Direct CFE manipulation (last resort)

Round-Up Failover:
  Primary: Event listener on wallet transactions
  Failover: Queue worker reprocess failed round-ups
  Manual: Batch reprocess via CLI
```

### SLA Targets
```
Service              Availability    Response Time
────────────────────────────────────────────────
Auto-Save execution  > 99.5%        30s from trigger
Round-Up execution   > 99.5%        10s from source txn
Goal creation        > 99.9%        < 2s p95
Deposit              > 99.9%        < 3s p95
Withdrawal           > 99.9%        < 3s p95
Profit distribution  > 99.0%        < 30 min (batch)
Dashboard load       > 99.9%        < 1s p95
Transaction history  > 99.9%        < 1s p95
```
