# Savings Monitoring

## Key Performance Indicators (KPIs)

### Business KPIs
```
Metric                          Alert Threshold        Frequency
──────────────────────────────  ─────────────────────  ──────────
Total savings under management  < 0 change (decline)   Daily
New goals created (daily)       < 100/day              Daily
Goal completion rate            < 30%                  Monthly
Auto-save adoption rate         < 20% of savers        Weekly
Round-up adoption rate          < 10% of savers        Weekly
Average goal size               < 100K SYP             Weekly
Early withdrawal rate           > 15%                  Monthly
Team goals created              < 10/day               Weekly
Profit return rate              < 0.1% monthly         Monthly
```

### Technical KPIs
```
Metric                          Target          Alert
──────────────────────────────  ─────────────── ─────────────────
Auto-save success rate          > 99%            < 98% over 1h
Round-up success rate           > 99%            < 97% over 1h
Profit distribution latency     < 30 min         > 60 min
Goal creation latency (p99)     < 2s             > 5s
Deposit latency (p99)           < 3s             > 8s
Withdrawal latency (p99)        < 3s             > 8s
API availability (savings)      > 99.9%          < 99.5%
Cron job failure rate           < 0.1%           > 1%
Queue backlog (savings_high)    < 100            > 1,000
```

## Monitoring Dashboards (Grafana)

### Dashboard: Savings Operations
```
Panel 1: Savings Overview (Stat)
  - Total goals (active/completed/cancelled)
  - Total saved amount (SYP)
  - Total profit distributed (SYP)
  - Active users with goals

Panel 2: Auto-Save Health (Time Series)
  - Auto-save executions per hour
  - Auto-save success rate %
  - Auto-save skipped (insufficient balance) count
  - Auto-save failed count

Panel 3: Round-Up Health (Time Series)
  - Round-up executions per hour
  - Round-up total amount (SYP)
  - Round-up success rate %
  - Top round-up sources (transaction types)

Panel 4: Goal Creation (Time Series)
  - Goals created per day
  - Goals completed per day
  - Goals cancelled per day
  - Average goal target amount

Panel 5: Profit Distribution (Stat + Table)
  - Last distribution total
  - Number of goals receiving profit
  - Average return rate
  - Management fee collected

Panel 6: Queue Health (Time Series)
  - savings_high queue depth
  - savings_low queue depth
  - savings_bulk queue depth
  - Processing time per job type
```

## Logging Strategy

### Structured Logging (JSON)
```php
// Auto-save execution log
Log::channel('savings')->info('autosave.executed', [
    'goal_id' => $goal->id,
    'user_id' => $goal->user_id,
    'amount' => 5000,
    'balance_before' => 1245000,
    'balance_after' => 1250000,
    'cfe_reference' => 'cfe_ref_xyz',
    'duration_ms' => 245,
    'execution_id' => uniqid(),
]);

// Round-up execution log
Log::channel('savings')->info('roundup.executed', [
    'goal_id' => $goal->id,
    'user_id' => $goal->user_id,
    'amount' => 500,
    'source_txn_id' => 'txn_wallet_789',
    'original_amount' => 23500,
    'rounded_amount' => 24000,
    'cfe_reference' => 'cfe_ref_roundup_abc',
    'duration_ms' => 180,
]);

// Profit distribution log
Log::channel('savings')->info('profit.distributed', [
    'period' => '2026-05',
    'pool_total' => 50000000,
    'pool_return' => 150000,
    'management_fee' => 15000,
    'net_profit' => 135000,
    'goals_count' => 1234,
    'total_distributions' => 135000,
    'duration_ms' => 45000,
]);
```

### Log Channels
```php
// config/logging.php
'channels' => [
    'savings' => [
        'driver' => 'daily',
        'path' => storage_path('logs/savings.log'),
        'level' => 'info',
        'days' => 90,
    ],
    'savings-autosave' => [
        'driver' => 'daily',
        'path' => storage_path('logs/savings-autosave.log'),
        'level' => 'info',
        'days' => 90,
    ],
    'savings-errors' => [
        'driver' => 'daily',
        'path' => storage_path('logs/savings-errors.log'),
        'level' => 'error',
        'days' => 365,
    ],
    'savings-reconciliation' => [
        'driver' => 'daily',
        'path' => storage_path('logs/savings-reconciliation.log'),
        'level' => 'info',
        'days' => 365,
    ],
];
```

## Alerts (PagerDuty / OpsGenie)

### Critical Alerts (Immediate Response)
```
1. Auto-save success rate < 95% for 10 consecutive minutes
   → Automated check: is CFE down?
   → Page: Savings engineering on-call

2. Profit distribution failed entirely (0 distributed)
   → Automated check: is pool calculation correct?
   → Page: Savings engineering + Finance ops

3. Goal creation API p99 latency > 10s for 5 minutes
   → Automated check: DB connection pool, CFE latency
   → Page: Backend engineering on-call

4. Any cron job fails for 3 consecutive runs
   → Automated check: process status
   → Page: Backend engineering on-call

5. Reconciliation discrepancy > 0.1% of total savings
   → Automated: freeze new deposits/withdrawals
   → Page: Savings engineering + Compliance
```

### Warning Alerts (Next Business Day)
```
1. Auto-save skip rate > 20% (users not funding wallets)
   → Investigate: marketing campaign for wallet funding?

2. Goal cancellation rate > 10% of active goals
   → Investigate: user feedback, UX issue?

3. Round-up adoption declining > 5% week-over-week
   → Investigate: feature discoverability?

4. Team goal creation declining > 20% month-over-month
   → Investigate: invite flow issues?

5. Queue backlog > 500 for savings_low
   → Investigate: scale workers?
```
