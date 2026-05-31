# Settlement Financial Flows

## Flow 1: EOD Batch Settlement

### Step-by-Step
```
Schedule: Daily at 23:00 (Asia/Damascus timezone)

Phase 1 — Collect
──────────────────────────────────────────────────────
Step 1: Trigger
  Cron: RunEndOfDaySettlement command runs at 23:00
  > Queries all transactions since last cut-off (23:00 previous day)
  > WHERE settlement_status = 'pending' AND deleted_at IS NULL

Step 2: Group by Entity
  Transactions: 12,450 total
  → Bank (Bemo Saudi Fransi):      5,400 txns | DR 12,000,000 | CR 57,000,000
  → Biller (Syriatel):             2,800 txns | DR 18,500,000 | CR  6,000,000
  → Biller (MTN):                  1,200 txns | DR  8,000,000 | CR  2,000,000
  → Merchant (Various):            2,000 txns | DR  4,000,000 | CR 15,000,000
  → Agent Network:                 1,050 txns | DR 10,000,000 | CR  6,500,000

Phase 2 — Create Batch
──────────────────────────────────────────────────────
Step 3: Create SettlementBatch record
  Batch: STL-20260529-0001
  Type: EOD
  Status: DRAFT
  Cut-off: 2026-05-28 23:00 → 2026-05-29 23:00
  Total: 12,450 transactions, 125,800,000 SYP

Step 4: Create SettlementBatchItem records
  One per entity grouping (5 items)

Phase 3 — Netting
──────────────────────────────────────────────────────
Step 5: Calculate Net Positions for each item

  Bank (Bemo Saudi Fransi):
    Total Debit (owed to Beza):     12,000,000
    Total Credit (owed to bank):    57,000,000
    Net: 57,000,000 - 12,000,000 = +45,000,000 (Beza PAYS bank)

  Biller (Syriatel):
    Total Debit:                    18,500,000
    Total Credit:                    6,000,000
    Net: 6,000,000 - 18,500,000 = -12,500,000 (Beza RECEIVES from biller)

  Biller (MTN):
    Total Debit:                     8,000,000
    Total Credit:                    2,000,000
    Net: 2,000,000 - 8,000,000 = -6,000,000 (Beza RECEIVES from MTN)

  Merchant (Various):
    Total Debit:                     4,000,000
    Total Credit:                   15,000,000
    Net: 15,000,000 - 4,000,000 = +11,000,000 (Beza PAYS merchants)

  Agent Network:
    Total Debit:                    10,000,000
    Total Credit:                    6,500,000
    Net: 6,500,000 - 10,000,000 = -3,500,000 (Beza RECEIVES from agents)

  Batch Net: +45,000,000 -12,500,000 -6,000,000 +11,000,000 -3,500,000 = +34,000,000 SYP
  → Net outflow: 34,000,000 SYP from Beza to external entities

Phase 4 — Double-Entry Accounting
──────────────────────────────────────────────────────
Step 6: Create CFE Journal Entries

  DR: Beza Settlement Pool Account       34,000,000 SYP
  CR: Beza CFE Main Ledger               34,000,000 SYP
  (Transfer from CFE to settlement pool for EOD payouts)

  DR: Biller (Syriatel) Account          12,500,000 SYP
  CR: Beza Settlement Pool Account       12,500,000 SYP
  (Collect from Syriatel — they owe Beza)

  DR: Biller (MTN) Account                6,000,000 SYP
  CR: Beza Settlement Pool Account        6,000,000 SYP
  (Collect from MTN — they owe Beza)

  DR: Agent Network Account               3,500,000 SYP
  CR: Beza Settlement Pool Account        3,500,000 SYP
  (Collect from agents — net they owe)

  DR: Beza Settlement Pool Account       45,000,000 SYP
  CR: Bank (Bemo Saudi Fransi) Account   45,000,000 SYP
  (Pay to bank — Beza owes them)

  DR: Beza Settlement Pool Account       11,000,000 SYP
  CR: Merchant Account                   11,000,000 SYP
  (Pay to merchants — Beza owes them)

Phase 5 — Generate Payment Orders
──────────────────────────────────────────────────────
Step 7: Create payment files per entity

  Payment Order PO-001: Bemo Saudi Fransi
    Direction: Pay (outgoing)
    Amount: 45,000,000 SYP
    File: PO_BSF_20260529.csv

  Payment Order PO-002: Syriatel (internal netting)
    Direction: Receive (book entry)
    Amount: 12,500,000 SYP
    → No external file needed, CFE book entry

  Payment Order PO-003: MTN (internal netting)
    Direction: Receive (book entry)
    Amount: 6,000,000 SYP

  Payment Order PO-004: Merchant Pool
    Direction: Pay (outgoing)
    Amount: 11,000,000 SYP
    File: PO_MERCH_20260529.csv

Phase 6 — Transmit & Confirm
──────────────────────────────────────────────────────
Step 8: Transmit payment orders to banks
  PO-001: Sent to Bemo Saudi Fransi API — Reference: BSF-REF-8877
  PO-004: Sent to merchant bank (MT103) — Reference: MCH-REF-9988

Step 9: Wait for confirmations (up to 60 min window)
  → Bank confirms PO-001: 45,000,000 SYP ✓
  → Merchant bank confirms PO-004: 11,000,000 SYP ✓

Phase 7 — Reconcile
──────────────────────────────────────────────────────
Step 10: Run reconciliation
  Internal amounts vs external confirmations
  12,430/12,450 items matched (99.84%)
  20 items unmatched → Exceptions created

Phase 8 — Settle
──────────────────────────────────────────────────────
Step 11: Mark batch as settled
  Status: SETTLED
  Match rate: 99.84%
  18/20 exceptions resolved automatically (tolerance within 100 SYP)
  2 exceptions escalated for manual review
```

### Sequence Diagram
```
Cron(23:00)   SettlementService   BatchService   NettingService   OrderService   Reconciliation   CFE Ledger
    │               │                  │              │               │               │               │
    │── EOD ───────>│                  │              │               │               │               │
    │               │── collect ──────>│              │               │               │               │
    │               │<── txns ────────│              │               │               │               │
    │               │                  │              │               │               │               │
    │               │── create ───────>│              │               │               │               │
    │               │<── batch ────────│              │               │               │               │
    │               │                  │              │               │               │               │
    │               │── process ──────>│── net ──────>│               │               │               │
    │               │                  │<── positions │               │               │               │
    │               │                  │── journals ──────────────────────────────────────────────>│
    │               │                  │── generate ────────────────>│               │               │
    │               │                  │<── orders ─────────────────│               │               │
    │               │                  │              │               │               │               │
    │               │                  │── transmit ─────────────────────>│           │               │
    │               │                  │              │               │<── confirm ───│               │
    │               │                  │              │               │               │               │
    │               │                  │── reconcile ──────────────────────────────>│               │
    │               │                  │              │               │               │               │
    │               │                  │── settle ────│               │               │               │
    │               │<── done ────────│              │               │               │               │
```

## Flow 2: Real-Time Settlement

### Step-by-Step
```
Trigger: Instant payment transaction committed

Step 1: Transaction Completes
  User sends 100,000 SYP to merchant via instant payment
  → Transaction recorded in CFE with settlement_entity = merchant_xyz
  → settlement_type = 'realtime'

Step 2: Settlement Trigger
  SettlementService.executeRealTime(transaction) fires
  → Check merchant settlement preference: realtime
  → Check minimum amount: 100,000 >= 50,000 threshold ✓

Step 3: Create Single-Transaction Batch
  Batch: RT-20260529-0001
  Type: REALTIME
  Items: 1 (Merchant XYZ, amount +100,000)

Step 4: Immediate Netting
  Merchant XYZ:
    Total Credit: 100,000
    Total Debit: 0
    Net: +100,000 (Beza PAYS merchant — immediately)

Step 5: Double-Entry (Two-Phase Commit)
  Phase 1 (Prepare):
    DR: Beza Settlement Pool Account      100,000 SYP
    CR: Beza CFE Main Ledger              100,000 SYP

  Phase 2 (Commit):
    DR: Beza Settlement Pool Account      100,000 SYP
    CR: Merchant Settlement Account       100,000 SYP

  → Both entries must succeed or entire batch rolls back

Step 6: Generate & Transmit Payment Order
  Payment Order: PO-RT-001
  Direction: Pay
  Amount: 100,000 SYP
  Sent to merchant's bank via API

Step 7: Confirm
  Bank confirms within 2 seconds
  → Reconciliation match created (auto-matched)

Step 8: Settle
  Batch status: SETTLED
  Transaction marked as settled
  Merchant notified: "تم تسوية 100,000 ل.س فورياً"
```

## Flow 3: Exception Handling

### Step-by-Step
```
Trigger: Reconciliation detects mismatch

Step 1: Mismatch Detected
  ReconciliationService compares internal vs external
  Batch Item: Bank Bemo Saudi Fransi
  Internal Amount: 45,000,000 SYP
  External Amount: 45,005,000 SYP
  Difference: 5,000 SYP

Step 2: Exception Created
  Exception EXC-001 created
  Type: amount_mismatch
  Severity: medium (difference 5,000 < 100,000 threshold)
  Status: OPEN

Step 3: Batch Held
  Batch STL-20260529-0001 → ON_HOLD
  Hold reason: "Exception EXC-001: amount mismatch (internal 45,000,000 ≠ external 45,005,000)"

Step 4: Ops Notified
  Dashboard alert: "⚠️ استثناء تسوية — دفعة STL-20260529-0001 معلقة"
  Email sent to operations team
  SMS sent for high/critical exceptions

Step 5: Investigation
  Ops lead (Layla) opens exception detail:
  → Downloads bank statement from partner portal
  → Sees: Bank credited 45,005,000 SYP, deducted 5,000 SYP as transfer fee
  → Actual settlement amount: 45,000,000 SYP (correct)
  → Notes: "رسوم تحويل 5,000 ل.س تم خصمها من قبل المصرف"

Step 6: Resolution
  Resolution Type: adjustment
  → Create CFE adjustment: DR Bank Charges 5,000 SYP, CR Settlement Pool 5,000 SYP
  Notes: "Bank deducted 5,000 SYP transfer fee — recorded as bank charge"
  Attachment: Bank statement PDF uploaded

Step 7: Batch Released
  Exception EXC-001 → RESOLVED
  No other open exceptions for this batch
  Batch STL-20260529-0001 → AWAITING_CONFIRMATION (released)

Step 8: Settlement Continues
  Batch proceeds to confirmation and final settlement
```

### Exception Resolution Types
```
1. Adjustment: Create correcting journal entry (e.g., bank fee)
   DR: Bank Charges Account    5,000 SYP
   CR: Settlement Pool         5,000 SYP

2. Manual Match: Force match despite difference (within policy)
   Used when difference is due to timing (exchange rate, rounding)

3. Write-Off: Accept small difference as operational cost
   Policy: Differences < 1,000 SYP auto-write-off
   DR: Write-Off Expense       500 SYP
   CR: Settlement Pool         500 SYP

4. Reprocess: Regenerate and retransmit payment order
   Used when transmission failed or wrong amount sent

5. Accepted Tolerance: Difference is within allowed tolerance
   No journal entry needed — system records tolerance match

6. Rejected: Escalate — cannot resolve internally
   Batch remains on hold until bank/counterparty resolves
```
