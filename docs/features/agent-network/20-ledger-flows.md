# Agent Network Ledger Flows

## Account Structure

### Chart of Accounts (Agent-Specific)
| Code | Account Name | Type | Normal Balance |
|------|-------------|------|---------------|
| 1103 | Agent Float SYP | Asset | Debit |
| 1104 | Agent Float USD | Asset | Debit |
| 2102 | Customer Wallet Clearing | Liability | Credit |
| 3101 | Beza Fee Income | Revenue | Credit |
| 3103 | Beza Commission Expense | Expense | Debit |
| 4102 | Agent Commission Payable | Liability | Credit |
| 4103 | Agent Commission Settled | Liability | Credit |
| 5101 | Agent Float Settlement Clearing | Asset | Debit |
| 5102 | Agent Funding Clearing | Asset | Debit |

### Journal Entry Patterns

#### Agent Cash-in
```
Cash-in (100,000 SYP, agent commission 500 SYP)
Timestamp: 2026-06-01T10:30:00Z
Reference: CI-20260601-87142

DR  1103  Agent Float SYP (Agent 10234)        100,000
CR  2102  Customer Wallet Clearing              100,000
-- Agent float decreases, customer clearing increases

DR  2102  Customer Wallet Clearing              100,000
CR  1101  Customer SYP Wallets (Umm Khaled)     100,000
-- Customer wallet credited (via wallet module)

CR  4102  Agent Commission Payable (Agent 10234)     500
DR  3103  Beza Commission Expense                     500
-- Commission accrued (0.5% of 100,000)
```

#### Agent Cash-out
```
Cash-out (50,000 SYP, fee 750 SYP, agent commission 375 SYP)
Timestamp: 2026-06-01T11:00:00Z
Reference: CO-20260601-45231

DR  1101  Customer SYP Wallets (Umm Khaled)      50,750
CR  2102  Customer Wallet Clearing                50,750
-- Customer wallet debited (amount + fee)

DR  2102  Customer Wallet Clearing                50,750
CR  1103  Agent Float SYP (Agent 10234)           50,000
CR  3101  Beza Fee Income                            750
-- Agent float credited for cash amount, fee recognized

CR  4102  Agent Commission Payable (Agent 10234)     375
DR  3103  Beza Commission Expense                     375
-- Commission accrued (0.75% of 50,000)
```

#### Float Top-up from Wallet
```
Agent tops up float: 500,000 SYP from personal wallet
Timestamp: 2026-06-01T14:00:00Z
Reference: FT-20260601-12345

DR  1101  Customer SYP Wallets (Agent 10234)     500,000
CR  5102  Agent Funding Clearing                  500,000
-- Debit agent's personal wallet

DR  5102  Agent Funding Clearing                  500,000
CR  1103  Agent Float SYP (Agent 10234)           500,000
-- Credit agent float account
```

#### Agent-to-Agent Float Transfer
```
Transfer 500,000 SYP from Agent 10235 to Agent 10234
Timestamp: 2026-06-01T15:00:00Z
Reference: AT-20260601-67890

DR  1103  Agent Float SYP (Agent 10235)          500,000
CR  5101  Agent Float Settlement Clearing         500,000
-- Debit source agent float

DR  5101  Agent Float Settlement Clearing         500,000
CR  1103  Agent Float SYP (Agent 10234)           500,000
-- Credit target agent float
```

#### Float Top-up via Cash at Hub
```
Agent deposits 1,000,000 SYP cash at Beza hub
Timestamp: 2026-06-01T16:00:00Z
Reference: FH-20260601-54321

DR  1105  Cash on Hand (Beza Hub)              1,000,000
CR  5102  Agent Funding Clearing                1,000,000
-- Cash received at hub

DR  5102  Agent Funding Clearing                1,000,000
CR  1103  Agent Float SYP (Agent 10234)         1,000,000
-- Agent float credited
```

#### Daily Commission Settlement
```
Settle all agent commissions for day (example: total 5,000,000 SYP across 200 agents)
Timestamp: 2026-06-02T03:00:00Z
Reference: SET-20260601

DR  4102  Agent Commission Payable              5,000,000
CR  1101  Customer SYP Wallets (Various Agents)  5,000,000
-- All accrued commissions settled to agent wallets

DR  3103  Beza Commission Expense                5,000,000
CR  4102  Agent Commission Payable               5,000,000
-- Zero out the payable/expense for settled period
-- (Or more simply: DR 4102, CR 1101 per agent; expense already recorded per-txn)
```

## Daily Settlement Process

```
Step 1: At 23:59, calculate agent float positions:
  - Total agent float SYP balance: 12,500,000,000 SYP
  - Total customer cash-in today: 1,500,000,000 SYP
  - Total customer cash-out today: 850,000,000 SYP
  - Net float movement: -650,000,000 SYP (more cash-in than cash-out)

Step 2: End-of-day float snapshot:
  INSERT INTO agent_float_snapshots (agent_id, balance, recorded_at)
  SELECT id, float_balance, NOW() FROM agents WHERE status = 'active';

Step 3: Fee income reconciliation:
  Total cash-out fees today: 12,750,000 SYP (1.5% of 850M)
  DR  2102  Customer Wallet Clearing             12,750,000
  CR  3101  Beza Fee Income                       12,750,000

Step 4: Commission expense reconciliation:
  Total commissions accrued today: 13,500,000 SYP
  (Already recorded per-transaction via DR Commission Expense)

Step 5: Reconcile agent float:
  Expected float = Yesterday's float
                   + Cash-out volume (agent credited)
                   + Float top-ups (all sources)
                   - Cash-in volume (agent debited)
                   - Agent-to-agent transfers (net)
  
  Alert if: |Actual - Expected| > 5,000 SYP tolerance
```

## Reconciliation Checks
```
Daily Reconciliation (Automated, 02:00 AM):

1. Agent Float Balance Check:
   SELECT agent_id, float_balance FROM agents WHERE status = 'active'
   vs
   SELECT agent_id, SUM(CASE WHEN type IN ('cash_out','float_funding','float_transfer_in','commission')
                             THEN amount ELSE -amount END) as calculated_balance
   FROM agent_transactions WHERE created_at < TODAY
   GROUP BY agent_id
   → Must match within 5,000 SYP per agent

2. Commission Accrual Check:
   SELECT SUM(amount) FROM agent_commissions WHERE status = 'accrued'
   vs
   SELECT SUM(pending_commission) FROM agents
   → Must match (zero variance)

3. Fee Income Check:
   SELECT SUM(fee) FROM agent_transactions
   WHERE type = 'cash_out' AND status = 'completed' AND DATE(created_at) = YESTERDAY
   vs
   SELECT SUM(amount) FROM ledger_entries
   WHERE account_code = '3101' AND DATE(created_at) = YESTERDAY
   → Must match within 0.1%

4. Float-to-Cash Ratio Check:
   SUM(agent float) / SUM(customer wallet balances)
   → Should be between 0.05 and 0.50
   → Alert if outside range (indicates systemic float imbalance)

5. Offline Transaction Check:
   SELECT COUNT(*) FROM agent_transactions
   WHERE offline_queued = true AND synced_at IS NULL
   → Alert if > 100

Alert if any check fails → Slack #ops-finance + email to finance@beza.com
```
