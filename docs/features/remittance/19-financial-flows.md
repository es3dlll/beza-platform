# Remittance Financial Flows

## Flow 1: Local P2P SYP Transfer

### Scenario: Ahmad sends 50,000 SYP to his mother in Aleppo

### Step-by-Step
```
Step 1: Validate & Hold
  Sender: Ahmad (Damascus)
  Recipient: Umm Ahmad (Aleppo) — phone +963912345678
  Amount: 50,250 SYP (transfer 50,000 + fee 250)
  Currency: SYP
  State: Available → Held
  Hold Expires: 30 minutes

Step 2: Authorize
  Check: Sufficient balance ✓ (has 200,000 SYP)
  Check: Daily limit (350,000 used + 50,250 = 400,250 < 500,000 ✓)
  Check: Recipient exists and is active ✓
  Check: Fraud score (5/100 → allow)
  Check: No self-transfer ✓

Step 3: Post (Double-Entry)
  DR: Sender SYP Main Wallet       50,250 SYP
  CR: Recipient SYP Main Wallet    50,000 SYP
  CR: Beza Fee Income Account         250 SYP
  Reference: TXN-REM-ABC123

Step 4: Release Hold
  Hold ID: hold_rem_456 → Released

Step 5: Update Balances
  Sender: 200,000 → 149,750 SYP
  Recipient: 100,000 → 150,000 SYP
  Fee Account: 0 → 250 SYP

Step 6: Emit Events
  - TransferSent(remittance_id: "rem_abc123")
  - TransferReceived(recipient_id: 92, amount: 50,000)
  - WalletDebited(sender, 50,250 SYP)
  - WalletCredited(recipient, 50,000 SYP)

Summary:
  Sender pays:    50,250 SYP
  Recipient gets: 50,000 SYP
  Beza revenue:      250 SYP (0.5%)
  Duration: ~3 seconds
```

### Sequence Diagram (Text)
```
Sender App          API Gateway      RemittanceService       CFE         Recipient
    │                    │                  │                 │              │
    │── POST send ──────>│                  │                 │              │
    │                    │── Validate ─────>│                 │              │
    │                    │                  │── Check Limits >│              │
    │                    │                  │<── Limits OK ──│              │
    │                    │                  │                 │              │
    │                    │                  │── Hold ────────>│              │
    │                    │                  │<── Hold OK ────│              │
    │                    │                  │                 │              │
    │                    │                  │── Post Entries >│              │
    │                    │                  │<── Post OK ────│              │
    │                    │                  │                 │              │
    │                    │                  │── Release Hold >│              │
    │                    │                  │                 │              │
    │                    │                  │── Save TXN ────>│              │
    │                    │                  │── Emit Events ──>              │
    │                    │                  │                 ├── Notify ───>│
    │<── Response ───────│<── 200 OK ──────│                 │              │
    │                    │                  │                 │              │
```

## Flow 2: Diaspora USD→SYP Remittance (with FX Conversion)

### Scenario: Khalid in Berlin sends $500 USD to his mother in Damascus, who receives SYP

### Step-by-Step
```
Step 1: Validate Corridor & FX
  Sender: Khalid (Berlin, Germany)
  Beneficiary: Umm Mohammed (Damascus, Syria)
  Corridor: USD_US->SYP (USA corridor, but Khalid uses USD wallet)
  FX Rate: 1 USD = 12,400 SYP (Beza rate)
  Mid-market rate: 12,590 SYP/USD
  Beza spread: 1.5%
  FX Lock: Rate locked for 60 seconds

Step 2: Calculate Fees
  Amount: $500.00 USD
  Remittance fee (1.5%): $7.50 USD
  FX spread income: $500 × 1.5% = $7.50 USD equivalent in SYP spread
  Total sender debit: $507.50 USD
  Target amount: $500 × 12,400 = 6,200,000 SYP
  Recipient gets: 6,200,000 SYP

Step 3: Hold & Authorize
  Hold: Sender USD wallet holds $507.50
  Check: Sufficient balance ✓ (has $1,200 USD)
  Check: Daily limit ($200 used + $507.50 = $707.50 < $1,000 ✓)
  Check: Monthly limit ($1,500 used + $507.50 = $2,007.50 < $10,000 ✓)
  Check: Source of funds — "salary" ✓ (required for >$500)
  Check: Sanctions screening — sender: PASSED, beneficiary: PASSED
  Check: Travel rule (>$1,000) — NOT required ($500 < $1,000 ✓)
  Check: Fraud score (12/100 → allow)

Step 4: Execute Conversion & Post
  DR: Sender USD Wallet                    $507.50 USD
  CR: Recipient SYP Wallet            6,200,000 SYP
  CR: Beza Fee Income (fee)                  $7.50 USD
  CR: Beza FX Income (spread)           93,000 SYP
  Reference: REM-DEF456

  FX Income Calculation:
    Mid-market: $500 × 12,590 = 6,295,000 SYP
    Beza rate: $500 × 12,400 = 6,200,000 SYP
    FX spread income: 6,295,000 - 6,200,000 = 95,000 SYP
    Less: Correspondent bank cost: 2,000 SYP
    Net FX income: 93,000 SYP

Step 5: Release Hold
  Hold ID: hold_rem_789 → Released

Step 6: Update Balances
  Sender USD: $1,200.00 → $692.50 USD
  Recipient SYP: 150,000 → 6,350,000 SYP
  Fee Income: +$7.50 USD
  FX Income: +93,000 SYP

Step 7: Notifications
  SMS to Umm Mohammed: "تم استلام 6,200,000 ل.س من خالد. الرصيد: 6,350,000 ل.س"
  Push to Khalid: "تم استلام 6,200,000 ل.س من قبل والدتك"
  Email receipt to Khalid: rem_def456.pdf

Summary:
  Sender pays:      $507.50 USD
  Recipient gets:   6,200,000 SYP
  Beza revenue:     $7.50 (fee) + 93,000 SYP (FX spread) = ~$15 total
  Effective cost:   2.8% ($14.50 / $507.50)
  Duration: ~8 seconds
```

## Flow 3: Recurring Monthly Remittance

### Scenario: Fatima in Stockholm has a recurring €200/month transfer to her father in Damascus

### One-time Setup (Day 0)
```
Setup Date: 2026-06-15
  Sender: Fatima (Stockholm, Sweden)
  Beneficiary: Abu Khaled (Damascus, Syria)
  Amount: €200.00
  Frequency: Monthly, 1st of each month
  Duration: Ongoing
  FX Preference: Execute at current rate (not locked at setup)
  First Execution: 2026-07-01

  Confirmation:
    "سيتم تحويل 200 يورو إلى والدك في اليوم الأول من كل شهر
     ابتداءً من 01-07-2026"
```

### Execution 1 — 2026-07-01 08:00:00 CET
```
Step 1: System triggers ExecuteRecurringAction (cron job)

Step 2: Get live FX rate
  EUR→SYP mid-market: 13,420
  Beza rate: 13,200 (1.8% spread)
  FX lock: Not locked (executing now)

Step 3: Check sender balance
  Fatima EUR wallet: €850.00
  Required: €200 + €3.00 fee (1.5%) = €203.00
  Balance OK ✓

Step 4: Check limits
  Daily: €0 used + €203 = €203 < €1,500 ✓
  Monthly: €1,200 used + €203 = €1,403 < €15,000 ✓

Step 5: Compliance screening
  Beneficiary sanctions re-check: PASSED (last screened 30 days ago)
  Travel rule: Not required (€200 < €1,000 threshold) ✓

Step 6: Execute transfer (same as Flow 2, amounts differ)
  DR: Fatima EUR Wallet                   €203.00
  CR: Abu Khaled SYP Wallet          2,640,000 SYP
  CR: Beza Fee Income                       €3.00
  CR: Beza FX Income                   44,000 SYP

  Target calculation: €200 × 13,200 = 2,640,000 SYP

Step 7: Update recurring record
  executions_count: 0 → 1
  failed_count: 0
  last_executed_at: 2026-07-01T08:00:00Z
  next_execution_at: 2026-08-01T08:00:00Z
  total_sent_amount: €200.00
  status: active (still ongoing)

Step 8: Notifications
  SMS to Abu Khaled: "تم استلام 2,640,000 ل.س من فاطمة. الرصيد: 2,790,000 ل.س"
  Push to Fatima: "تم تنفيذ التحويل الشهري - 2,640,000 ل.س إلى والدك"
  Email receipt to Fatima

Summary:
  Sender pays:      €203.00
  Recipient gets:   2,640,000 SYP
  Beza revenue:     €3.00 (fee) + 44,000 SYP (FX spread)
  Duration: ~10 seconds (automated)
```

### Execution 2 — 2026-08-01 (Failed + Retry)
```
08:00:00 — First attempt: Insufficient balance (Fatima had €150, not €203)
  → Retry scheduled: 09:00

08:00:01 — Event: TransferFailed(reason: "insufficient_balance")
  → Notification sent to Fatima: "فشل التحويل الشهري بسبب رصيد غير كافٍ"

09:00:00 — Retry #1: Still insufficient (€180)
  → Retry scheduled: 15:00

15:00:00 — Retry #2: Sufficient (Fatima topped up €100)
  → Success! Transfer completed
  → Recipient gets 2,640,000 SYP (at rate 13,100, slightly different)

Edge Case Resolution:
  failed_count: 0 → 1 → 2 (retries) → 0 (success on 3rd)
  Retry policy: 1h → 6h → 24h → Fail permanently → Notify user
```

### Recurring Cancellation Scenario
```
2026-12-15: Fatima pauses recurring (father visiting Sweden)
  → Status: paused
  → next_execution_at: NULL (cleared until resume)

2027-02-01: Fatima resumes recurring
  → Status: active
  → next_execution_at: 2027-03-01T08:00:00Z

2027-06-01: After 12 executions, Fatima cancels
  → Status: cancelled
  → Total sent: €2,400 (12 × €200)
  → Confirmation: "تم إلغاء التحويل الشهري. تم تحويل إجمالي 2,400 يورو"
```
