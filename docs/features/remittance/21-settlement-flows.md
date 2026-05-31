# Remittance Settlement Flows

## Settlement Types

### Instant Settlement (Default)
```
Trigger: Every completed remittance
Mechanism: Real-time double-entry posting via CFE
Sender: Debited immediately in source currency
Recipient: Credited immediately in target currency (post-FX)
FX: Converted at locked or current rate
Ledger: Immediate debit/credit on wallet + nostro accounts
```

### Batch Settlement (Corridors)
```
Trigger: End of day (23:59 CET, daily)
Scope: Net positions per corridor
Mechanism: Netting of all remittances in corridor → single nostro movement
Execution: Automated cron job after market close
Confirmation: Settlement report generated at 01:00 daily

Example (EUR→SYP Corridor, Day X):
  Total incoming EUR: €45,000 (from diaspora senders)
  Total outgoing SYP: 594,000,000 SYP (to recipients)
  Beza holds: €45,000 in EUR nostro account
  Beza needs: 594,000,000 SYP for disbursement
  
  Settlement Action:
    1. Beza sells €45,000 on FX market → gets ~594,000,000 SYP
    2. SYP credited to Beza local bank account
    3. Recipient wallets already credited (real-time), 
       settlement is backend treasury movement
```

### Correspondent Bank Settlement
```
Trigger: Daily batch settlement
Mechanism: SWIFT MT103 or local clearing
Parties: Beza → Correspondent Bank (e.g., Deutsche Bank) → Local Partner Bank in Syria

Flow:
  1. Beza aggregates all EUR from German diaspora senders
  2. Beza sends SWIFT MT103 to Deutsche Bank: €45,000
  3. Deutsche Bank credits Beza's nostro account
  4. Beza uses EUR to buy SYP via FX desk
  5. SYP transferred to Beza's local Syrian bank account
  6. Recipient wallets already credited (real-time, pre-funded)

Settlement Timing:
  - EU corridors: T+0 (same day, EU transfer cut-off 14:00 CET)
  - US corridors: T+0 (if before 16:00 ET) or T+1
  - Turkey corridors: T+0 via local partner (pre-funded float)
```

### Suspense Settlement (Unclaimed Funds)
```
Trigger: Recipient not on Beza
Mechanism: Funds held in Remittance Suspense account
Duration: 90 days
Actions:
  - Day 0: Funds moved to suspense, SMS sent with claim code
  - Day 7: Reminder SMS
  - Day 30: Reminder SMS + push to sender to notify recipient
  - Day 60: Final notice
  - Day 90: Funds reversed to sender (minus reversal fee)

Reversal Flow:
  DR  5101  Remittance Suspense                6,200,000 SYP
  CR  1101  Customer SYP Wallets (Sender)       6,185,000 SYP  (minus 15,000 fee)
  CR  3101  Beza Reversal Fee Income               15,000 SYP
```

## Settlement Flow (Corridor: EUR→SYP via Germany)

```
Day Timeline:

08:00 — First remittance of the day (€300 → 3,960,000 SYP)
  → Real-time: Debit sender EUR wallet, credit recipient SYP wallet
  → Backend: EUR held in Beza settlement pool

12:00 — 50 remittances processed: €22,500 total
  → Recipients already received 297,000,000 SYP
  → Beza has €22,500 in EUR settlement pool

14:00 — Cut-off for same-day EUR settlement
  → Final count: €45,000 total
  → Beza initiates SWIFT MT103 to Deutsche Bank

14:05 — Deutsche Bank confirms receipt of €45,000
  → Beza EUR Nostro credited: €45,000

14:30 — Beza FX desk executes EUR→SYP conversion
  → Rate: 13,200 SYP/EUR
  → Beza receives: 594,000,000 SYP in local bank account

15:00 — Reconciliation:
  Total SYP disbursed (today):         594,000,000 SYP
  Total SYP received from FX:          594,000,000 SYP
  Difference: 0 ✓
  
  Fee income accrued: €675 (€45,000 × 1.5%)
  FX spread income: 8,910,000 SYP
  Correspondent bank cost: €225 (0.5%)
  Net revenue: €450 + 8,910,000 SYP
```

## Settlement Flow (Local P2P — No Settlement Required)

```
Local P2P transfers are internal to Beza:
  - Sender and recipient both have Beza wallets
  - No FX conversion needed
  - No correspondent bank involvement
  - Net position: always zero (one wallet debited, another credited)
  - Only fee income is recognized

Example:
  1,000 local P2P transfers today
  Total sent: 50,000,000 SYP
  Total fees: 250,000 SYP (0.5%)
  Net: 0 (all internal credits/debits match)
  Revenue: 250,000 SYP from fees
```

## Reconciliation

### Daily Reconciliation
```
1. Match remittances to wallet transactions:
   SELECT id FROM remittances WHERE date = TODAY AND status = 'completed'
   vs
   SELECT remittance_id FROM wallet_transactions WHERE date = TODAY
   → Every remittance has matching debit + credit

2. Corridor position check:
   SELECT SUM(source_amount) FROM remittances WHERE date = TODAY AND corridor_id = 1
   vs
   SELECT SUM(amount) FROM nostro_account WHERE corridor_id = 1 AND date = TODAY
   → Must match within 1%

3. FX rate consistency:
   SELECT AVG(fx_rate), corridor_id FROM remittances WHERE date = TODAY GROUP BY corridor_id
   vs
   SELECT rate FROM fx_rate_logs WHERE date = TODAY
   → All executed rates must match locked/current rates at time of execution

4. Revenue reconciliation:
   SELECT SUM(fee) as fee_income, SUM(fx_spread_income) as fx_income
   FROM remittances WHERE date = TODAY
   vs
   SELECT SUM(amount) FROM revenue_ledger WHERE source = 'remittance' AND date = TODAY
   → Must match
```

### Exception Handling
```
Mismatch < 50,000 SYP or < $10: Auto-adjust with memo
Mismatch 50,000-500,000 SYP or $10-100: Flag for manual review, notify ops
Mismatch > 500,000 SYP or > $100: Halt settlement, notify finance + engineering

FX Rate Discrepancy:
  If a rate used differs from locked rate by > 0.5%:
  → Flag for immediate review
  → Reverse transfer if in 24h window
  → Notify compliance
```
