# Bill Payment Settlement Flows

## Settlement Types

### Per-Biller Settlement Schedule
| Biller | Settlement Frequency | Settlement Method | Payment Terms |
|--------|---------------------|-------------------|---------------|
| PEED | Daily (T+1) | Bank transfer to PEED account at CBL | Net amount collected − 0.5% commission |
| Damascus Water | Weekly (Sunday) | Bank transfer to Water Authority account | Net amount collected − 0.75% commission |
| Syriatel | Daily (T+1) | Bank transfer to Syriatel designated account | Net amount collected − 1% − 100 SYP fixed |
| MTN | Daily (T+1) | Bank transfer to MTN designated account | Net amount collected − 1% − 100 SYP fixed |
| Syria Telecom | Weekly (Monday) | Bank transfer to SPT account | Net amount collected − 0.5% commission |
| Aya Internet | Bi-weekly (Wed) | Bank transfer to Aya corporate account | Net amount collected − 1% commission |
| Saman Internet | Bi-weekly (Wed) | Bank transfer to Saman corporate account | Net amount collected − 1% commission |
| Government Fees | Monthly (1st) | Bank transfer to Ministry of Finance consolidated account | Net amount collected − 1.5% commission |
| University Fees | Per semester | Bank transfer to each university account | Net amount collected − 1% commission |

### Instant Settlement (Default)
```
Trigger: Every completed bill payment
Mechanism: Real-time double-entry posting in Beza ledger
Biller: Settlement account credited immediately (Beza side)
User: Wallet debited immediately
Biller Payout: Per schedule above (T+1, weekly, etc.)
Ledger:
  DR Customer Wallet
  CR Biller Settlement Account
  CR Beza Commission Income
```

### Commission Withholding
```
Each payment automatically withholds Beza commission:

PEED Example:
  Bill amount: 44,625 SYP
  Beza commission (0.5%): 224 SYP
  Net to PEED: 44,401 SYP (transferred T+1)
  
  Entry:
  DR  Biller Settlement — PEED         44,625
  CR  Bank — PEED Settlement           44,401  (actual transfer to PEED)
  CR  3301  Bill Payment Commission      224   (Beza retains)
```

## Settlement Flow (PEED Daily)

```
PEED Settlement — June 10, 2026:
  Total PEED Bills Paid: 1,250 transactions
  Total Amount Collected: 52,100,000 SYP
  Total Commission (0.5%): 260,500 SYP
  Net to PEED: 51,839,500 SYP

T+1 Settlement (June 11):
  08:00 — Generate settlement report
  09:00 — Submit payment instruction to bank
  12:00 — Bank processes transfer to PEED account at CBL
  14:00 — Confirmation received from PEED
  14:05 — Update settlement status in system
  14:10 — Notify finance team: settlement complete

Settlement Report — PEED 2026-06-11:
  ┌──────────────────┬────────────┬─────────────┐
  │ Metric           │ Amount SYP │ # Txns      │
  ├──────────────────┼────────────┼─────────────┤
  │ Gross Collected  │ 52,100,000 │ 1,250       │
  │ Commission (0.5%)│ 260,500    │ —           │
  │ Net Transfer     │ 51,839,500 │ —           │
  │ Bank Ref         │ CBL-TRF-20260611-042  │
  │ Status           │ Completed  │             │
  └──────────────────┴────────────┴─────────────┘
```

## Settlement Flow (Government Fees — Monthly)

```
Government Fees Settlement — July 1, 2026 (for June):
  Total Collected: 12,450,000 SYP
  Total Commission (1.5%): 186,750 SYP
  Net to MoF: 12,263,250 SYP

Monthly Reconciliation:
  Step 1: Verify all June csv_billable_items match bill_transactions
  Step 2: Generate per-ministry breakdown:
    - Ministry of Justice: 3,200,000 SYP
    - Ministry of Interior (Passports): 5,800,000 SYP
    - Ministry of Foreign Affairs: 1,950,000 SYP
    - Other: 1,500,000 SYP
    Total: 12,450,000 SYP
  Step 3: Submit consolidated settlement to Ministry of Finance
  Step 4: MoF distributes to individual ministries
  Step 5: Mark all June csv_batch_files as 'settled'
```

## Settlement Flow (CSV Batch — University Fees)

```
Damascus University — Semester Settlement (Feb 2026):
  CSV File Received: 2026-02-01
  Total Records: 8,500 students
  Total Tuition: 1,275,000,000 SYP
  Paid via Beza: 420 students = 63,000,000 SYP
  Commission (1%): 630,000 SYP
  Net to University: 62,370,000 SYP

  Settlement:
  ┌──────────────────┬──────────────────┐
  │ Date             │ Event            │
  ├──────────────────┼──────────────────┤
  │ Feb 1            │ CSV uploaded     │
  │ Feb 1–Mar 15     │ Students pay     │
  │ Mar 16           │ Batch closed     │
  │ Mar 17           │ Settlement calc  │
  │ Mar 18           │ Bank transfer    │
  │ Mar 20           │ Confirmed        │
  └──────────────────┴──────────────────┘
```

## Reconciliation

### Daily Reconciliation
```
1. Per-biller transaction count match:
   SELECT biller_id, COUNT(*), SUM(total) FROM bill_transactions
   WHERE paid_at::date = CURRENT_DATE AND status = 'paid'
   GROUP BY biller_id
   vs
   Biller settlement account totals from ledger

2. Commission income match:
   SELECT SUM(fee) FROM bill_transactions
   WHERE paid_at::date = CURRENT_DATE AND status = 'paid'
   vs
   3301 Account total from ledger (bill payment income only)

3. CSV batch match:
   SELECT COUNT(*), SUM(amount) FROM csv_billable_items
   WHERE paid_at::date = CURRENT_DATE AND status = 'paid'
   vs
   Government/university bill transaction totals
```

### Exception Handling
```
Mismatch < 5,000 SYP: Auto-adjust with memo "Daily settlement rounding"
Mismatch 5,000–50,000 SYP: Flag for manual review, notify ops
Mismatch > 50,000 SYP: Halt settlement, notify finance + engineering + biller

Common Exceptions:
  - Duplicate payment (user paid twice): refund one via biller reversal
  - Biller API double-count: verify biller_connection_logs for duplicate confirm
  - CSV payment without batch record: manual reconciliation with government
  - Batch record without payment: mark as expired after due date
```
