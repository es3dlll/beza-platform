# Bill Payment Ledger Flows

## Account Structure

### Chart of Accounts (Bill Payment-Specific)
| Code | Account Name | Type | Normal Balance |
|------|-------------|------|---------------|
| 1201 | Biller Settlement — PEED | Liability | Credit |
| 1202 | Biller Settlement — Damascus Water | Liability | Credit |
| 1203 | Biller Settlement — Syriatel | Liability | Credit |
| 1204 | Biller Settlement — MTN | Liability | Credit |
| 1205 | Biller Settlement — Syria Telecom | Liability | Credit |
| 1206 | Biller Settlement — Aya Internet | Liability | Credit |
| 1207 | Biller Settlement — Saman Internet | Liability | Credit |
| 1208 | Biller Settlement — Government Fees | Liability | Credit |
| 1209 | Biller Settlement — University Fees | Liability | Credit |
| 1301 | CSV Batch Clearing (Government) | Asset | Debit |
| 3301 | Bill Payment Commission Income | Revenue | Credit |
| 3302 | Late Fee Processing Income | Revenue | Credit |

### Journal Entry Patterns

#### Bill Payment (PEED Electricity — Real-time API)
```
PEED Electricity Payment (44,625 SYP bill + 224 SYP fee = 44,849 SYP)
Timestamp: 2026-06-10T09:30:05Z
Reference: BILL-PEED-20260610-ABCDEFGHIJ

DR  1101  Customer SYP Wallets (Ahmad Khaled)        44,849
CR  1201  Biller Settlement — PEED                   44,625
CR  3301  Bill Payment Commission Income                 224
-- User wallet debited; biller settlement + Beza income credited
-- PEED settled per contract (typically T+1 or weekly batch)

Note: The 44,625 SYP held in "Biller Settlement — PEED" represents
funds belonging to PEED that Beza will remit per settlement schedule.
```

#### Bill Payment (Syriatel Mobile Postpaid)
```
Syriatel Payment (33,000 SYP bill + 430 SYP fee = 33,430 SYP)
Timestamp: 2026-06-05T08:15:00Z
Reference: BILL-SYR-20260605-XXXXXXXXXX

DR  1101  Customer SYP Wallets (Ahmad Khaled)        33,430
CR  1203  Biller Settlement — Syriatel               33,000
CR  3301  Bill Payment Commission Income                330  (1% of 33,000)
CR  3301  Bill Payment Commission Income                100  (fixed fee)
-- Syriatel commission: 1% + 100 SYP fixed = 430 total
```

#### Bill Payment (Damascus Water)
```
Water Bill Payment (8,500 SYP bill + 64 SYP fee = 8,564 SYP)
Timestamp: 2026-06-08T14:00:00Z
Reference: BILL-WATER-20260608-XXXXXXXXXX

DR  1101  Customer SYP Wallets (Customer)             8,564
CR  1202  Biller Settlement — Damascus Water          8,500
CR  3301  Bill Payment Commission Income                  64  (0.75%)
```

#### CSV Batch Bill Payment (Government Fees)
```
Government Fees Payment (8,000 SYP fees + 120 SYP fee = 8,120 SYP)
Timestamp: 2026-06-10T11:00:00Z
Reference: BILL-GOV-20260610-XXXXXXXXXX

Step 1: Upon user payment:
DR  1101  Customer SYP Wallets                        8,120
CR  1208  Biller Settlement — Government Fees         8,000
CR  3301  Bill Payment Commission Income                 120  (1.5%)

Step 2: Upon government CSV reconciliation (batch EOD):
DR  1208  Biller Settlement — Government Fees         8,000
CR  1301  CSV Batch Clearing (Government)             8,000
-- Funds moved from settlement to clearing for batch transfer to government
```

#### Auto-pay (Recurring Monthly)
```
PEED Auto-pay (46,200 SYP bill + 231 SYP fee = 46,431 SYP)
Timestamp: 2026-06-15T08:00:05Z
Reference: BILL-PEED-20260615-AUTOPAY-XXXXX

DR  1101  Customer SYP Wallets                       46,431
CR  1201  Biller Settlement — PEED                   46,200
CR  3301  Bill Payment Commission Income                 231  (0.5%)
-- No additional auto-pay fee for premium; standard commission only
```

#### Late Fee Processing (When Beza charges convenience fee)
```
Late Fee Processing (2,125 SYP late fee + 500 SYP convenience)
Timestamp: 2026-06-10T09:30:05Z
Reference: BILL-PEED-20260610-LATE-XXXXX

DR  1101  Customer SYP Wallets                       44,849  (42,500 + 2,125 + 224)
     -- Included in the main payment entry above
CR  1201  Biller Settlement — PEED                   44,625  (42,500 bill + 2,125 late fee)
CR  3301  Bill Payment Commission Income                224  (0.5%)

-- The late fee (2,125 SYP) flows entirely to PEED as part of total billing
-- Beza does not retain any portion of late fees unless separately agreed
```

## Daily Settlement Process (Bill Payment)

```
Step 1: At 23:59, calculate per-biller settlement:

Biller Settlement Summary — 2026-06-10:
┌─────────────────────┬──────────┬──────────┬──────────┬──────────┐
│ Biller              │ Txns     │ Volume   │ Comm.    │ Net      │
│                     │          │ (SYP)    │ (SYP)    │ (SYP)    │
├─────────────────────┼──────────┼──────────┼──────────┼──────────┤
│ PEED                │ 1,250    │ 52.1M    │ 260.5K   │ 52.1M    │
│ Damascus Water      │ 340      │ 3.2M     │ 24.0K    │ 3.2M     │
│ Syriatel            │ 980      │ 28.5M    │ 383.0K   │ 28.5M    │
│ MTN                 │ 720      │ 19.8M    │ 296.0K   │ 19.8M    │
│ Syria Telecom       │ 180      │ 1.8M     │ 9.0K     │ 1.8M     │
│ Aya Internet        │ 420      │ 8.4M     │ 84.0K    │ 8.4M     │
│ Saman Internet      │ 310      │ 5.2M     │ 52.0K    │ 5.2M     │
│ Government Fees     │ 50       │ 0.4M     │ 6.0K     │ 0.4M     │
│ University Fees     │ 25       │ 1.2M     │ 12.0K    │ 1.2M     │
├─────────────────────┼──────────┼──────────┼──────────┼──────────┤
│ Total               │ 4,275    │ 120.6M   │ 1.126M   │ 120.6M   │
└─────────────────────┴──────────┴──────────┴──────────┴──────────┘

Step 2: Journal entry for daily settlement:

DR  1201  Biller Settlement — PEED                   52,100,000
DR  1202  Biller Settlement — Damascus Water          3,200,000
DR  1203  Biller Settlement — Syriatel               28,500,000
DR  1204  Biller Settlement — MTN                    19,800,000
DR  1205  Biller Settlement — Syria Telecom           1,800,000
DR  1206  Biller Settlement — Aya Internet             8,400,000
DR  1207  Biller Settlement — Saman Internet           5,200,000
DR  1208  Biller Settlement — Government Fees            400,000
DR  1209  Biller Settlement — University Fees           1,200,000
CR  5101  Settlement Clearing (Bank)                120,600,000
-- All biller settlement accounts swept to settlement clearing
-- Actual bank transfer to billers occurs per contract (T+1, weekly, etc.)

Step 3: Commission income recognition:

CR  3301  Bill Payment Commission Income              1,126,000
DR  5101  Settlement Clearing (Bank)                  1,126,000
-- Commission income crystallized; net settlement to Beza bank account
```

## Reconciliation Checks

```
Daily Reconciliation (Bill Payment, 03:00 AM):

1. Per-Biller Volume Check:
   SELECT biller_id, SUM(total) FROM bill_transactions
   WHERE date = YESTERDAY AND status = 'paid'
   GROUP BY biller_id
   vs
   Biller settlement account total from journal entries
   → Must match exactly

2. Fee Reconciliation:
   SELECT biller_id, SUM(fee) FROM bill_transactions
   WHERE date = YESTERDAY AND status = 'paid'
   GROUP BY biller_id
   vs
   Commission income entries by biller
   → Must match within 1,000 SYP tolerance

3. Customer Wallet Total:
   SELECT SUM(total) FROM bill_transactions
   WHERE date = YESTERDAY AND status = 'paid'
   vs
   SUM of wallet debits from journal entries with type 'bill_payment'
   → Must match exactly

4. CSV Batch Reconciliation:
   SELECT COUNT(*), SUM(amount) FROM csv_billable_items
   WHERE status = 'paid' AND paid_at = YESTERDAY
   vs
   Government fee bill transaction totals
   → Must match

5. Biller Settlement Transfer:
   Verify that settlement amounts are transferred to each biller per contract:
   - PEED: Daily T+1 bank transfer
   - Damascus Water: Weekly (Sunday)
   - Syriatel: Daily T+1
   - MTN: Daily T+1
   - Syria Telecom: Weekly (Monday)
   - Aya/Saman: Bi-weekly (Wednesday)
   - Government: Monthly (1st of month)
   - Universities: Per semester batch

Alert if any check fails → Slack #ops-finance
```
