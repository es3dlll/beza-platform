# Merchant Financial Flows

## Flow 1: QR Payment (Static QR)

### Step-by-Step
```
Step 1: Customer scans merchant's static QR code
   QR data: "beza://pay/merchant/42?type=static"
   Customer app decodes → shows merchant name + amount input

Step 2: Customer enters amount (45,000 SYP)
   Validated: min 1,000 SYP, max merchant per-txn limit 1,000,000 SYP

Step 3: Hold customer wallet
   Account: Customer Main Wallet (SYP)
   Amount: 45,000 SYP
   State: Available → Held
   Reason: "QR payment to متجر الشمّام"
   Expires: 30 minutes

Step 4: Authorize
   Check: Customer balance sufficient ✓
   Check: Fraud score (8/100 → allow)
   Check: Merchant active and can accept ✓

Step 5: Post (Double-Entry)
   DR: Customer Main Wallet              45,000 SYP
   CR: Merchant Settlement Account       45,000 SYP (gross)
   
   (Future: MDR post via settlement engine at EOD)
   Reference: TXN-MER-ABC123

Step 6: Release Hold
   Hold ID: hold_789 → Released
   Reason: "QR payment completed TXN-MER-ABC123"

Step 7: Create merchant_transaction record
   merchant_id: 42
   amount: 45,000
   mdr_rate: 1.5%
   mdr_amount: 675
   net_amount: 44,325
   method: qr
   status: completed

Step 8: Emit Events
   - QrPaymentCompleted(merchant, customer, amount, mdr)
   - WalletDebited(customer, 45,000)

Step 9: Notifications
   - Merchant: Push + Voice "تم استلام 45,000 ل.س"
   - Customer: SMS receipt + push
```

### Sequence Diagram (Text)
```
Customer App        API Gateway         QrService           CFE             Merchant App
    │                    │                  │                 │                 │
    │── Scan QR ────────>│                  │                 │                 │
    │── Enter Amt ──────>│                  │                 │                 │
    │                    │── Validate QR ──>│                 │                 │
    │                    │<── QR Valid ─────│                 │                 │
    │                    │                  │                 │                 │
    │                    │── Hold Amt ──────────────────────>│                 │
    │                    │<───────────────── Hold OK ────────│                 │
    │                    │                  │                 │                 │
    │                    │── Post Debit ────────────────────>│                 │
    │                    │── Post Credit ────────────────────>│                 │
    │                    │<───────────── Post Complete ──────│                 │
    │                    │                  │                 │                 │
    │                    │── Release Hold ──────────────────>│                 │
    │                    │                  │                 │                 │
    │                    │── Save Txn ─────>│                 │                 │
    │                    │── Emit Event ───>│                 │                 │
    │                    │                  │                 ├── Notify ──────>│
    │<── Success ────────│<── 200 OK ──────│                 │   (WS/Push)     │
    │                    │                  │                 │   "تم 45,000"   │
```

## Flow 2: Payment Link

### Step-by-Step
```
Step 1: Merchant creates payment link
   Amount: 45,000 SYP
   Description: "شنطة ظهر جلدية"
   Expiry: 1 hour
   Short URL: https://pay.beza.com/pay/pl_abc123

Step 2: Merchant shares link via WhatsApp

Step 3: Customer opens link in browser/Beza app
   If Beza app installed: deep link → pay screen
   If browser: mobile-optimized payment page

Step 4: Customer sees:
   Merchant: "متجر الشمّام"
   Amount: 45,000 SYP
   Description: "شنطة ظهر جلدية"
   Pay button

Step 5: Customer confirms → same CFE flow as QR payment
   Hold → Post debit customer → Post credit merchant → Release

Step 6: Payment link marked as PAID
   Status: pending → paid
   paid_at: now()

Step 7: Notifications
   Merchant: Push + WhatsApp "تم دفع 45,000 ل.س — شنطة ظهر جلدية"
   Customer: Receipt

Edge Cases:
   - Link expired: Customer sees error page → contact merchant
   - Link already paid: "تم دفع هذا الرابط مسبقاً"
   - Amount mismatch: Customer cannot change amount (locked)
```

## Flow 3: Daily Settlement

### Step-by-Step
```
Step 1: Cron triggers ProcessSettlementJob at 23:59

Step 2: For each active merchant with completed transactions today:

   Al-Sham Supermarket (Merchant #42):
     Today's completed transactions:
       TXN-001: 45,000 SYP (QR, 1.5%)
       TXN-002: 120,000 SYP (POS, 2.0%)
       TXN-003: 35,000 SYP (QR, 1.5%)
       TXN-004: 250,000 SYP (QR, 1.5%)
       TXN-005: 85,000 SYP (Link, 2.0%)
       TXN-006: 65,000 SYP (POS, 2.0%)
       TXN-007: 50,000 SYP (QR, 1.5%)
       TXN-008: 42,000 SYP (QR, 1.5%)
       TXN-009: 78,000 SYP (POS, 2.0%)
       TXN-010: 30,000 SYP (QR, 1.5%)
       TXN-011: 25,000 SYP (Link, 2.0%)
       TXN-012: 25,000 SYP (QR, 1.5%)

Step 3: Calculate
   Gross: 850,000 SYP
   QR MDR (1.5% × 7 txn = 525,000 × 1.5%): 7,875 SYP
   POS MDR (2.0% × 3 txn = 263,000 × 2.0%): 5,260 SYP
   Link MDR (2.0% × 2 txn = 110,000 × 2.0%): 2,200 SYP
   
   Total MDR: 15,335 SYP
   Wait — let me recalculate. The user said 1.5% QR, 2% POS, 2.5% Web. For links, 2%.
   
   Actually in the prompt, MDR rates: 1.5% QR, 2% POS, 2.5% web.
   Payment Links: we didn't specify but from the business case it's 2.0%.
   
   Let me redo:
   QR txns: 45,000 + 35,000 + 250,000 + 50,000 + 42,000 + 30,000 + 25,000 = 477,000
   QR MDR (1.5%): 7,155
   POS txns: 120,000 + 65,000 + 78,000 = 263,000
   POS MDR (2.0%): 5,260
   Link txns: 85,000 + 25,000 = 110,000
   Link MDR (2.0%): 2,200
   Total MDR: 14,615
   Net: 850,000 - 14,615 = 835,385

Step 4: Create settlement record (DB)
   merchant_id: 42
   period_start: 2026-06-01 00:00:00
   period_end: 2026-06-01 23:59:59
   gross: 850,000
   mdr: 14,615
   net: 835,385
   status: processing

Step 5: Post CFE Settlement (Double-Entry)
   DR: Merchant Settlement Clearing Account    835,385 SYP
   CR: Merchant Wallet (Merchant #42)          835,385 SYP

   DR: Merchant MDR Receivable                 14,615 SYP
   CR: Beza MDR Income Account                 14,615 SYP

Step 6: Mark settlement as completed
   status: processing → completed
   paid_at: now()
   Update all 12 merchant_transactions → settled = true

Step 7: Emit MerchantSettled event

Step 8: Notify merchant
   Push: "تم تسوية 835,385 ل.س — مبيعات 1 يونيو 2026"
   Merchant opens app → sees settlement details
```
