# Agent Network Settlement Flows

## Agent Commission Settlement

### Flow Overview
```
Commission Accrual (per transaction):
  ┌──────────┐     ┌──────────────┐     ┌──────────────┐
  │ Cash-in  │────>│ Commission    │────>│ Pending      │
  │ Txn      │     │ Service      │     │ Commission   │
  └──────────┘     │ calculate()  │     │ (Agent table)│
  ┌──────────┐     └──────────────┘     └──────┬───────┘
  │ Cash-out │                                  │
  │ Txn      │                                  │ Daily 03:00 AM
  └──────────┘                                  ▼
                                          ┌──────────────┐
                                          │ Settle Batch │
                                          │ (Batch Job)  │
                                          └──────┬───────┘
                                                 │
                                    ┌────────────┴────────────┐
                                    ▼                         ▼
                            ┌──────────────┐         ┌──────────────┐
                            │ Agent Wallet  │         │ Commission   │
                            │ Credited     │         │ Marked       │
                            │ (Real money) │         │ Settled      │
                            └──────────────┘         └──────────────┘
```

### T+1 Batch Settlement Process
```
Schedule: Daily at 03:00 AM Syria time (UTC+3)
Trigger: artisan agent:settle-commissions
System: Laravel Scheduler on agent-pos-api service

Algorithm:
  1. BEGIN TRANSACTION
  2. SELECT all agents WHERE pending_commission > 0
  3. FOR each agent:
     a. settlementAmount = agent.pending_commission
     b. IF agent.user_id IS NULL → skip (no wallet to credit)
     c. Credit agent's Beza wallet: settlementAmount
     d. INSERT INTO agent_commission_settlements:
        batch_reference = "SET-" + YYYYMMDD
        agent_id, settled_date = YESTERDAY, amount = settlementAmount
     e. INSERT INTO agent_commissions:
        mark all unsettled commissions for this agent as 'settled'
        with settlement_id
     f. UPDATE agents SET:
        pending_commission = 0
        total_commission_earned += settlementAmount
     g. INSERT INTO agent_transactions:
        type = 'commission', amount = +settlementAmount
  4. COMMIT TRANSACTION
  5. FOR each agent:
     a. Send SMS: "تم تسوية عمولات يوم أمس: X ل.س"
     b. Push in-app notification
     c. Emit CommissionSettled event

Concurrency: Lock agents table row-by-row to prevent race conditions
Retry: If settlement fails for an agent, retry 3 times, then flag for manual review
```

### Settlement Example
```
Batch: SET-20260601
Date: 2026-06-02 03:00 AM
Total Agents: 1,850
Total Amount: 5,275,000 SYP

Sample:
  Agent BZ-10234 (Abu Mohammad): 12,500 SYP → credited to wallet
  Agent BZ-10235 (Abu Khaled): 23,750 SYP → credited to wallet
  Agent BZ-10236 (Fatima Shop): 8,200 SYP → credited to wallet

Settlement Cutoff: All transactions with created_at < 2026-06-02 00:00:00
```

### Settlement Statuses
| Status | Description | Resolution |
|--------|-------------|------------|
| processing | Batch job running, funds being transferred | Wait for completion |
| completed | All agents settled successfully | None needed |
| failed_partial | Some agents failed (e.g., missing wallet) | Manual review of failed agents |
| failed | Complete batch failure | Check logs, retry job |

## Float Reconciliation

### End-of-Day Reconciliation (EOD)
```
Schedule: Daily at 23:59 Syria time
Process:
  1. For each active agent, calculate expected float:
     Expected = Opening float (from yesterday's snapshot)
                + Today's cash-out total
                + Today's float top-ups (wallet, cash, agent transfers in)
                - Today's cash-in total
                - Today's agent transfers out

  2. Compare with actual float_balance in agents table

  3. IF |Expected - Actual| > 5,000 SYP:
     a. Flag agent for reconciliation review
     b. Create reconciliation ticket in ops system
     c. Send alert to finance team

  4. Take snapshot:
     INSERT INTO agent_float_snapshots (agent_id, balance, recorded_at)
     SELECT id, float_balance, NOW() FROM agents WHERE status IN ('active', 'suspended');
```

### Float Discrepancy Investigation
```
Discrepancy > 5,000 SYP detected for Agent BZ-10234

Step 1: System auto-investigates:
  - Compare each transaction against ledger entries
  - Check for duplicate or missing transactions
  - Verify offline sync logs
  - Check for any pending/synced transactions

Step 2: Common causes:
  - Offline transaction double-processed: 90% of cases
  - Agent mis-keyed amount: 5%
  - System bug: 3%
  - Fraud: 2%

Step 3: Resolution:
  A. If duplicate offline sync: reverse the duplicate, adjust float
  B. If agent mis-key: adjust float, warn agent
  C. If system bug: fix, adjust float, post-mortem
  D. If fraud: suspend agent, escalate to compliance

Step 4: Manual adjustment:
  INSERT INTO agent_transactions (type='adjustment', amount=±X, notes='...')
  UPDATE agents SET float_balance = float_balance ± X
```

## Automated vs Manual Settlement

### Automated Settlement (Default)
| Aspect | Detail |
|--------|--------|
| Trigger | Daily at 03:00 AM via cron |
| Agent eligibility | All active agents with pending_commission > 0 |
| Settlement to | Agent's personal Beza wallet |
| Fee | Free for agent |
| Notification | SMS + in-app push |
| Failed agent handling | Retry 3x, then flag for manual review |

### Manual Settlement (Exception)
```
When used:
  - Agent wallet creation delayed (user_id is null)
  - Settlement amount > 500,000 SYP for a single day (requires human approval)
  - Agent requested cash settlement instead of wallet
  - Automated settlement failed after 3 retries

Process:
  1. Finance team reviews exception report
  2. Either:
     a. Resolve wallet issue and trigger re-settlement
     b. Process offline settlement (cash payment at hub)
     c. Make manual ledger adjustment
  3. Document reason in settlement notes
```

### Settlement Limits and Controls
```
Per-agent daily settlement max: 500,000 SYP (above requires finance approval)
Total daily settlement max: 50,000,000 SYP (system-wide)
Settlement hold: No hold — commissions are immediately available after settlement
Settlement reversal: Within 24h if commission was calculated incorrectly
```

## Exception Handling

### Commission Calculation Error
```
Scenario: Agent BZ-10234 was paid 500 SYP for a 100,000 SYP cash-in,
but should have been paid 300 SYP (Bronze rate 0.3% → was mistakenly 0.5%)

Detection:
  - Automated reconciliation detects rate mismatch
  - Or agent reports overpayment/underpayment

Resolution:
  1. If overpaid:
     a. Create reversal commission entry: -200 SYP
     b. Debit agent wallet: 200 SYP
     c. Notify agent: "تم تعديل عمولة المعاملة CI-87142"
  2. If underpaid:
     a. Create additional commission entry: +200 SYP
     b. Credit agent wallet: 200 SYP (in next settlement)
     c. Notify agent: "تم إضافة 200 ل.س عمولة إضافية"
```

### Settlement Batch Failure
```
Scenario: Batch SET-20260601 failed at 80% completion (power outage)

Impact: 1,480 agents settled, 370 agents not settled

Recovery:
  1. On restart, detect incomplete batch
  2. Roll back partially completed settlements (reverse wallet credits)
  3. Retry entire batch from beginning
  4. If batch fails again: alert engineering, manual processing

Prevention: Each agent settlement is atomic (DB transaction per agent).
Batch failure only affects agents not yet processed.
```
