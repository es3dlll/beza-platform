# Savings Financial Flows

## Flow 1: Auto-Save Execution

### Step-by-Step
```
Schedule: Daily at 10:00 (or user-configured time)

Step 1: Trigger
  Cron: ProcessScheduledAutoSaves command runs
  > Queries savings_goals WHERE status=active AND auto_save_enabled=true
    AND (last_execution IS NULL OR last_execution < today)
    AND auto_save_time <= CURRENT_TIME

Step 2: For each due goal — Check Balance
  Account: User Main Wallet (SYP)
  Amount: 5,000 SYP (auto_save_amount)
  Check: Available balance >= 5,000 SYP
  → If insufficient: Skip, log auto_save_logs(skipped, reason="insufficient_balance")

Step 3: Execute Transfer (Double-Entry)
  DR: User Main Wallet              5,000 SYP
  CR: Savings Goal Sub-Wallet       5,000 SYP
  Reference: autosave-{goal_id}-{timestamp}

Step 4: Update Balances
  Main Wallet: 125,000 → 120,000 SYP
  Savings Sub-Wallet: 1,245,000 → 1,250,000 SYP

Step 5: Record
  - savings_transactions: type=deposit, sub_type=auto_save, amount=+5,000
  - auto_save_logs: status=completed
  - Update goal.current_amount: +5,000
  - Update next_execution: tomorrow 10:00

Step 6: Emit Events
  - AutoSaveExecuted(goal, 5,000)
  - Check milestone → GoalMilestoneReached(50%) if applicable

Step 7: Push Notification
  "تم توفير 5,000 ل.س تلقائياً في هدف «لابتوب جديد»"
```

### Sequence Diagram
```
Cron                 AutoSaveService         CFE              Goal DB          Push Service
 │                        │                   │                 │                  │
 │── process() ──────────>│                   │                 │                  │
 │                        │── Find due goals ─>│                 │                  │
 │                        │<── Goal list ─────│                 │                  │
 │                        │                   │                 │                  │
 │                        │── Check balance ──>│                 │                  │
 │                        │<── Balance OK ────│                 │                  │
 │                        │                   │                 │                  │
 │                        │── Debit wallet ───>│                 │                  │
 │                        │<── Debit OK ──────│                 │                  │
 │                        │                   │                 │                  │
 │                        │── Credit sub-wal ─>│                 │                  │
 │                        │<── Credit OK ─────│                 │                  │
 │                        │                   │                 │                  │
 │                        │── Save txn ────────────────────────>│                  │
 │                        │── Update goal ─────────────────────>│                  │
 │                        │── Log execution ───────────────────>│                  │
 │                        │                   │                 │                  │
 │                        │── Emit event ─────────────────────────────────────────>│
 │<── Processed ──────────│                   │                 │                  │
 │                        │                   │                 │                  │
```

## Flow 2: Round-Up Saving

### Step-by-Step
```
Trigger: Wallet transaction completed (e.g., grocery payment 23,500 SYP)

Step 1: Monitor
  RoundUpService.listen(WalletTransaction) fires after every completed wallet txn
  Skip if: 
    - Transaction type is savings-related (savings_deposit, savings_withdrawal)
    - User has no round-up enabled goals
    - Original amount is already at thousand boundary (e.g., 24,000)

Step 2: Calculate
  Original amount: 23,500 SYP
  Round to nearest: 1,000 SYP
  Rounded amount: ceil(23,500 / 1,000) × 1,000 = 24,000 SYP
  Round-up amount: 24,000 - 23,500 = 500 SYP

Step 3: Validate
  Check: Main wallet has >= 500 SYP remaining after original transaction
  Check: Daily round-up total + 500 <= daily_max (50,000)
  Check: Monthly round-up total + 500 <= monthly_max (500,000)
  Check: 500 >= min_round_amount (100)

Step 4: Execute Transfer (Double-Entry)
  DR: User Main Wallet                  500 SYP
  CR: Savings Goal Sub-Wallet           500 SYP
  Reference: roundup-{source_txn_id}

Step 5: Update Balances
  Main Wallet: 76,500 → 76,000 SYP (was 100,000, -23,500 for grocery, -500 for round-up)
  Savings Sub-Wallet: 1,250,000 → 1,250,500 SYP

Step 6: Record
  - savings_transactions: type=roundup, amount=+500
  - round_up_executions: original=23,500, rounded=24,000, round_up=500
  - Update goal.current_amount: +500

Step 7: Emit Events
  - RoundUpExecuted(goal, 500, source_txn)
  - Check milestone

Step 8: Micro-Animation in App
  "تم توفير 500 ل.س من فكة المشتريات! ↻"
```

## Flow 3: Goal Completion & Withdrawal

### Step-by-Step
```
Step 1: Target Reached
  Goal.current_amount (2,500,500) >= Goal.target_amount (2,500,000)
  → Trigger: CheckGoalCompletion job (runs hourly) OR real-time check on deposit

Step 2: Lock Period Check
  If goal_locked = true AND lock_release_date > now:
    → status = awaiting_release
    → Notification: "هدفك «لابتوب جديد» مكتمل! سيتم فتحه في {release_date}"
    → Stop here until lock expires

  If not locked OR lock expired:
    → Proceed to Step 3

Step 3: Mark Completed
  Goal.status = completed
  Goal.completed_at = now()
  Team goal (if team): team.status = completed

Step 4: Emit Events
  - GoalCompleted(goal)
  - GoalMilestoneReached(100%)

Step 5: Celebration
  - Full-screen celebration in app (confetti, message)
  - SMS: "مبروك! لقد حققت هدف «لابتوب جديد» في Beza! 🎉"
  - Shareable achievement card

Step 6: Withdrawal (User-initiated)
  User opens goal → taps "سحب"
  Selects amount: 2,500,000 SYP (full) or partial
  Confirms with PIN

Step 7: Execute Withdrawal (Double-Entry)
  DR: Savings Goal Sub-Wallet         2,500,000 SYP
  CR: User Main Wallet                2,500,000 SYP
  Reference: goal-completion-{goal_id}

Step 8: Update Balances
  Savings Sub-Wallet: 2,500,500 → 500 SYP (residual profit)
  Main Wallet: 76,000 → 2,576,000 SYP

Step 9: Record
  - savings_transactions: type=withdrawal, sub_type=goal_completion
  - Update goal: current_amount = 500 (residual)

Step 10: Prompt "ما هو هدفك القادم؟" → Create Goal Screen
```

## Flow 4: Profit Distribution

### Step-by-Step
```
Frequency: Monthly (1st of each month, 00:00)

Step 1: Calculate Pool
  Query: SUM(current_amount) FROM savings_goals WHERE status IN (active, awaiting_release)
  Pool total: 50,000,000 SYP (example)

Step 2: Get Pool Return from CFE
  CFE investment engine reports monthly return on pooled savings
  Pool return: 150,000 SYP (0.3% monthly return)

Step 3: Calculate Profit Pool
  Management fee: 150,000 × 10% = 15,000 SYP
  Net profit pool: 150,000 - 15,000 = 135,000 SYP

Step 4: For Each Active Goal — Calculate Proportional Share
  Goal A: current_amount = 10,000,000, weight = 10/50 = 0.20
    → Days held: 60 days, time_weight = min(60/30, 1.0) = 1.0
    → Profit: 135,000 × 0.20 × 1.0 = 27,000 SYP

  Goal B: current_amount = 5,000,000, weight = 5/50 = 0.10
    → Days held: 15 days, time_weight = min(15/30, 1.0) = 0.5
    → Profit: 135,000 × 0.10 × 0.5 = 6,750 SYP

  Goal C: current_amount = 35,000,000, weight = 35/50 = 0.70
    → Days held: 90 days, time_weight = min(90/30, 1.0) = 1.0
    → Profit: 135,000 × 0.70 × 1.0 = 94,500 SYP

Step 5: Distribute (Double-Entry for Each Goal)
  DR: Beza Profit Pool Account        135,000 SYP
  CR: Savings Goal A Sub-Wallet        27,000 SYP
  CR: Savings Goal B Sub-Wallet         6,750 SYP
  CR: Savings Goal C Sub-Wallet        94,500 SYP
  CR: Beza Management Fee Income        15,000 SYP

Step 6: Update
  - Profit distributions table: record each
  - Update goal.current_amount: + profit for each goal
  - savings_transactions: type=profit for each

Step 7: Emit Events
  - ProfitDistributed(goal A, 27,000)
  - ProfitDistributed(goal B, 6,750)
  - ProfitDistributed(goal C, 94,500)

Step 8: Push Notifications
  "تم توزيع 27,000 ل.س أرباحاً على هدف «لابتوب جديد»! 🌙"
```

## Flow 5: Early Withdrawal (Locked Goal)

### Step-by-Step
```
Step 1: User requests withdrawal from locked goal
  Goal: locked, release_date = 2026-12-01, current = 1,300,000
  Request: 500,000 SYP

Step 2: System checks lock status
  → Locked: early withdrawal penalty applies
  → Display warning: "سيتم خصم 2% رسوم سحب مبكر (10,000 ل.س)"
  → User confirms

Step 3: Calculate Penalty
  Penalty: 500,000 × 2% = 10,000 SYP
  Net to user: 500,000 - 10,000 = 490,000 SYP

Step 4: Execute (Double-Entry)
  DR: Savings Goal Sub-Wallet         500,000 SYP
  CR: User Main Wallet                490,000 SYP
  CR: Beza Early Withdrawal Income     10,000 SYP

Step 5: Update
  Goal.current_amount: 1,300,000 → 800,000
  Transaction: type=withdrawal, sub_type=early_withdrawal, penalty=10,000

Step 6: Emit Events
  - GoalWithdrawn(goal, 500,000, penalty=10,000)
```
