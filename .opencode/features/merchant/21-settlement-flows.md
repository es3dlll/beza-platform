# Merchant Settlement Flows

## Settlement Types

### Daily Settlement (Default)
```
Trigger: Every day at 23:59 (automated cron job)
Scope: All completed merchant transactions for the day
Period: T day (00:00:00 — 23:59:59)
Settlement Time: T+0 (same day, processed at midnight)
Payout: T+1 morning (merchant wallet credited by 01:00)
Mechanism: Net position calculation → single batch journal entry
Confirmation: Settlement report generated at 00:30 daily
```

### Instant Settlement (Premium)
```
Trigger: Per-transaction (real-time)
Scope: Individual transaction
Payout: Immediate to merchant wallet
Fee: Additional 0.5% on top of standard MDR
Availability: Tier 3+ merchants or Premium subscription
Mechanism: Individual CFE posting per transaction
```

### Manual Settlement (Support)
```
Trigger: Operations team initiates
Use Cases:
  - Settlement failed and was retried 3x automatically
  - Merchant dispute resolved in merchant's favor
  - System reconciliation adjustment
Authorization: Requires 2FA approval (finance + ops)
Mechanism: Admin panel → initiate settlement → CFE transfer
```

## Settlement Flow (Merchant Daily)

```
Merchant Al-Sham Supermarket (Tier: Small, MDR: QR 1.5%, POS 2.0%, Link 2.0%)

Day: 2026-06-01
  QR Sales (7 txns):     477,000 SYP
  POS Sales (3 txns):    263,000 SYP
  Link Sales (2 txns):   110,000 SYP
  ─────────────────────────────────
  Gross Total:           850,000 SYP

MDR Calculation:
  QR:    477,000 × 1.5%  =   7,155 SYP
  POS:   263,000 × 2.0%  =   5,260 SYP
  Link:  110,000 × 2.0%  =   2,200 SYP
  ─────────────────────────────────
  Total MDR:                     14,615 SYP

Net Settlement:
  Gross:     850,000 SYP
  MDR:       -14,615 SYP
  ─────────────────────────────────
  Net:       835,385 SYP

Settlement Entry (EOD Batch, 00:15):
  DR  Settlement Clearing      835,385
  CR  Merchant Wallet          835,385
  (Net settlement to merchant wallet)

  DR  MDR Receivable           14,615
  CR  MDR Income (QR)           7,155
  CR  MDR Income (POS)          5,260
  CR  MDR Income (Link)         2,200
```

## Settlement Schedule

```
Settlement Timeline:
  23:59 — Cron triggers ProcessSettlementJob
  00:00 — Load all merchants with daily transactions
  00:05 — Calculate settlements (gross, MDR, net per merchant)
  00:15 — Post CFE batch transfers (DR settlement clearing, CR merchant wallet)
  00:20 — Update merchant_transactions (set settled=true)
  00:25 — Mark settlements as completed, set paid_at
  00:30 — Emit MerchantSettled events
  00:35 — Generate settlement reports (PDF)
  01:00 — All merchant wallets credited

Timeline if settlement fails:
  00:15 — Settlement processing starts
  00:20 — CFE transfer fails for Merchant #55
  00:21 — Auto-retry #1 (immediate)
  00:25 — Auto-retry #2 (5 min delay)
  00:35 — Auto-retry #3 (15 min delay)
  00:55 — All retries exhausted → emit MerchantPayoutFailed
  00:56 — Alert ops team via Slack #ops-payments
  01:00 — Manual intervention required
```

## Settlement Report

```
Settlement Report — متجر الشمّام
─────────────────────────────────
التاريخ: 2026-06-01
رقم التقرير: STL-42-2026-06-01

ملخص التسوية:
  إجمالي المبيعات:    850,000 ل.س
  إجمالي الرسوم:       14,615 ل.س
  صافي التسوية:       835,385 ل.س
  عدد المعاملات:      12

تفاصيل حسب طريقة الدفع:
  QR Code (7 معاملات):     477,000 ل.س
    رسوم QR (1.5%):          7,155 ل.س
    صافي QR:               469,845 ل.س

  POS (3 معاملات):         263,000 ل.س
    رسوم POS (2.0%):         5,260 ل.س
    صافي POS:              257,740 ل.س

  رابط دفع (2 معاملات):    110,000 ل.س
    رسوم رابط (2.0%):        2,200 ل.س
    صافي رابط:             107,800 ل.س

حالة التسوية:    مكتملة ✓
تاريخ الدفع:     2026-06-02 00:15
مرجع CFE:        CFE_SETTLE_001
```

## Reconciliation

### Daily Reconciliation
```
1. Match all merchant_transactions to wallet_transactions
2. Verify gross amount matches sum of customer debits
3. Verify MDR calculation is correct per rate chart
4. Verify net settlement matches merchant wallet credit
5. Check all merchants with txns have settlement records
6. Generate exception report for mismatches
```

### Exception Handling
```
Mismatch < 5,000 SYP: Auto-adjust with memo, log for review
Mismatch 5,000-50,000 SYP: Flag for manual review, notify ops
Mismatch > 50,000 SYP: Halt all settlements, notify finance + engineering

Common exceptions:
  - Merchant suspended mid-day: Prorate settlement, hold balance
  - Disputed transaction: Exclude from settlement, move to suspense
  - Double settlement: Reverse one, investigate root cause
  - Missing transaction: Manual add to settlement, verify with merchant
```
