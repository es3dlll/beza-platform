# Savings User Journeys

## Journey 1: Create a Savings Goal
```
Step 1: User opens app → taps "Savings" from bottom tab or home card
Step 2: Taps "+" or "إنشاء هدف جديد"
Step 3: Names goal: "لابتوب جديد" (New Laptop)
Step 4: Sets target: 2,500,000 SYP
Step 5: Sets target date: 2026-12-01 (6 months from now)
Step 6: Chooses goal type: Individual
Step 7: (Optional) Enables auto-save: Daily 5,000 SYP
Step 8: (Optional) Toggles round-up: ON
Step 9: (Optional) Toggles goal lock: ON (savings locked until target reached)
Step 10: Reviews summary → confirms with PIN
Step 11: Goal created with dedicated sub-wallet
Step 12: Shows goal detail screen with progress at 0%

Edge Cases:
  - Name too long: max 100 chars, counter shows remaining
  - Target too low: min 50,000 SYP validation
  - Target date too close: min 7 days from now
  - No main wallet balance yet: prompt user to fund wallet first
  - Duplicate goal name: "لديك بالفعل هدف بنفس الاسم" warning
```

## Journey 2: Auto-Save Execution
```
Step 1: System cron triggers AutoSaveService (daily 10:00 or user-configured time)
Step 2: Check goal: auto_save_enabled = true, status = active
Step 3: Check main wallet balance ≥ auto_save_amount
Step 4: Debit main wallet: 5,000 SYP → hold → post
Step 5: Credit savings sub-wallet: 5,000 SYP
Step 6: Update goal current_amount: +5,000
Step 7: Record savings_transaction: type = deposit, auto_save
Step 8: Push notification: "تم توفير 5,000 ل.س تلقائياً هدف لابتوب جديد"
Step 9: Check milestone (25%, 50%, 75%, 100%) → celebration if reached

Edge Cases:
  - Insufficient balance: skip, mark skipped_autosave, retry next cycle
  - 3 consecutive skips: push notification "فاته 3 أيام من التوفير"
  - Wallet frozen/maintenance: skip with silent log
  - Concurrent deposit in progress: queue via lock
```

## Journey 3: Round-Up Saving
```
Step 1: User buys groceries: 23,500 SYP
Step 2: Wallet transaction completed successfully
Step 3: RoundUpService detects: round_up_enabled = true for user's active goals
Step 4: Calculates: ceiling(23,500 / 1000) × 1000 = 24,000
Step 5: Round-up amount: 24,000 - 23,500 = 500 SYP
Step 6: Debit main wallet: 500 SYP
Step 7: Credit savings goal sub-wallet: 500 SYP
Step 8: Record savings_transaction: type = roundup
Step 9: Show micro-animation in app: "تم توفير 500 ل.س من الفكة!"
Step 10: If user has multiple round-up eligible goals, round-robin or primary goal

Edge Cases:
  - Main wallet has < 500 SYP after main transaction: skip round-up, log
  - No active round-up goals: skip silently
  - Round-up amount < 100 SYP: still execute (every pound counts)
  - User pauses round-up: check toggle before execution
  - Transaction is a savings withdrawal: never round-up a withdrawal
```

## Journey 4: Goal Completion & Withdrawal
```
Step 1: Goal reaches 100% (current_amount ≥ target_amount)
Step 2: If locked: lock period starts (configurable, e.g., 7 days)
Step 3: User receives push: "مبروك! لقد حققت هدفك 🎉"
Step 4: After lock period (if any): "هدفك جاهز للسحب"
Step 5: User opens goal → taps "سحب" (Withdraw)
Step 6: Selects amount: partial (min 10,000) or full
Step 7: Confirms with PIN
Step 8: Debit savings sub-wallet → credit main wallet
Step 9: Record savings_transaction: type = withdrawal
Step 10: Goal status updated: if full withdrawal → completed
Step 11: User prompted to create next goal: "ما هو هدفك القادم؟"

Edge Cases:
  - Early withdrawal from locked goal: penalty 2%, user informed before confirm
  - Partial withdrawal before target: goal remains active
  - Withdrawal during profit calculation period: proportional profit settled
```

## Journey 5: Team Goal (Group Savings)
```
Step 1: User creates team goal: "رحلة العائلة" (Family Trip)
Step 2: Sets target: 5,000,000 SYP, target date: 6 months
Step 3: Generates invite code: "SAVE-FAMILY-42"
Step 4: Shares invite code with 4 family members via WhatsApp/SMS
Step 5: Members open app → Savings → "الانضمام إلى فريق" → enter code
Step 6: Each member sees team goal with all contributors listed
Step 7: Any member can deposit to team goal
Step 8: Contributions tracked per member: "أحمد: 500,000 | لينا: 300,000"
Step 9: Progress bar shows team total vs target
Step 10: Milestones celebrate team achievement

Edge Cases:
  - Member leaves team: their contributions returned (minus fees)
  - Team creator can remove inactive members (7 days no contribution)
  - Invite code expires: configurable (default 30 days)
  - Max team size: 20 members
  - Member reaches individual max contribution: notified
```
