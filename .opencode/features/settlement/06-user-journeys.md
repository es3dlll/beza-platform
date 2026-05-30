# Settlement User Journeys

## Journey 1: Daily EOD Settlement Run

**Who**: Layla (Operations Lead) | **Frequency**: Daily at 23:00

```
1. System auto-collects all pending transactions since last settlement cut-off
   → Layla receives notification: "بدأت دورة التسوية اليومية"

2. Batch service creates settlement batch grouping by entity type (banks, billers, merchants, agents)
   → Dashboard shows: "Batch #20260529-001 | 12,450 transactions | 125,800,000 SYP"

3. Netting engine calculates net positions for each entity
   → Layla reviews net position summary:
     - Bemo Saudi Fransi: +45,000,000 SYP (Beza owes bank)
     - Biller A (Syriatel): -12,500,000 SYP (biller owes Beza)
     - Merchant B: +8,000,000 SYP (Beza owes merchant)
     - Agent network: -3,500,000 SYP (net to collect)

4. Payment order service generates payment files
   → System creates payment_order_BSF_20260529.csv for Bemo Saudi Fransi
   → System creates payment_order_INTERNAL_20260529.csv for internal transfers

5. Layla reviews and approves batch
   → Clicks "اعتماد التسوية"
   → Batch status changes to "processing"

6. Payment orders transmitted to partners
   → Bank confirmations tracked via API polling
   → Internal settlements posted to CFE ledger

7. Reconciliation engine runs after 60-minute confirmation window
   → Amount matches: 12,430/12,450 items matched ✓
   → Mismatches: 20 items flagged for review
   → Layla investigates mismatches, resolves 18 (wrong references), escalates 2

8. Batch marked as "settled"
   → Dashboard: "✅ Batch #20260529-001 settled | 99.84% match rate"
   → Settlement report generated and archived
```

## Journey 2: Real-Time Settlement (Instant Payment)

**Who**: System (automated) | **Frequency**: Per-transaction

```
1. Merchant processes instant payment via Beza (100,000 SYP)
   → Payment is CFE-booked but not yet settled to merchant's external bank

2. Real-time settlement trigger fires:
   → Check merchant settlement preference: "real-time"
   → Check batch window: system detects this should bypass EOD batch

3. NettingService calculates: merchant net position after this payment
   → Merchant net: +100,000 SYP

4. Immediate payment order created and sent via bank API
   → Beza CFE posts: DR Merchant Settlement Account, CR Bank Settlement Account

5. Bank confirms receipt (within 2 seconds)
   → ReconciliationService creates match record
   → Settlement complete

6. Merchant receives notification: "تم تسوية 100,000 ل.س فورياً"
```

## Journey 3: Exception Resolution

**Who**: Layla (Operations Lead) | **Frequency**: As needed

```
1. Alert: "⚠️ استثناء تسوية في Batch #20260529-001 — مبلغ غير متطابق"
   → Amount: Internal records show 500,000 SYP, Bank confirmation shows 505,000 SYP
   → Difference: 5,000 SYP (bank credited more than expected)

2. Layla opens exception detail:
   → Transaction reference: TXN-BSF-20260529-4321
   → Bank reference: BSF-CONFIRM-20260529-8877
   → Internal amount: 500,000 SYP
   → Bank amount: 505,000 SYP
   → Status: PENDING_INVESTIGATION

3. Layla checks bank statement (uploaded via portal):
   → Bank statement shows 505,000 SYP with same reference
   → Notes: "الفارق 5,000 ل.س عمولة مصرفية تم خصمها"

4. Layla adds investigation note and adjusts
   → Resolution: "Bank deducted 5,000 SYP fee before crediting — record as bank charge"
   → Clicks "تسوية الاستثناء"
   → System creates adjustment entry: DR Bank Charges 5,000 SYP

5. Batch hold released, settlement proceeds
   → Exception logged with full audit trail
```
