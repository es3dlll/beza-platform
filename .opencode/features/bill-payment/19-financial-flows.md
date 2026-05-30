# Bill Payment Financial Flows

## Flow 1: Bill Fetch + Pay (Real-time API Connection)

### PEED Electricity Bill — Real Example
```
Customer: Ahmad Khaled
Customer ID: 1234-5678-9012-3456-7890
Biller: PEED (الشركة العامة للكهرباء)
Interface: Direct REST API

Step 1: Fetch Bill
  Request:  POST https://api.peed.gov.sy/v1/bill/inquiry
            { "customer_id": "123456789012345678901234" }
  Response: {
    "customer_name": "أحمد خالد",
    "address": "دمشق, المزة, شارع النصر, بناء 15",
    "invoice": "PE-2026-789012",
    "period": "مايو 2026",
    "consumption_kwh": 850,
    "tariff": "منزلي +250 ك.و",
    "amount": 42500,           // Consumption: 40,000 + Tax: 2,500
    "late_fee": 2125,           // 5% overdue penalty (5 days)
    "total_due": 44625,
    "due_date": "2026-06-15",
    "status": "unpaid",
    "biller_ref": "PE1234567890"
  }

Step 2: Calculate Beza Fee
  PEED commission: 0.5% × 44,625 = 224 SYP
  Total user charge: 44,625 + 224 = 44,849 SYP

Step 3: Verify Wallet Balance
  Available: 124,849 SYP
  Required: 44,849 SYP
  → Sufficient ✓

Step 4: Execute Payment
  a. CFE Hold: Debit wallet 44,849 SYP (hold)
  b. POST https://api.peed.gov.sy/v1/payment/confirm
     {
       "customer_id": "123456789012345678901234",
       "amount": 44625,
       "beza_reference": "BILL-PEED-20260610-ABCDEFGHIJ",
       "timestamp": "2026-06-10T09:30:00Z",
       "signature": "hmac_sha256_sig"
     }
  c. PEED Response:
     {
       "status": "completed",
       "biller_reference": "PE1234567890-CONFIRM",
       "confirmed_at": "2026-06-10T09:30:03Z"
     }
  d. CFE Post: Confirm wallet debit 44,849 SYP
  e. Release hold

Step 5: Complete Transaction
  Transaction saved with status: paid
  Receipt generated with both references
  Notifications sent (push + SMS)

Amount Flow:
  User Wallet:  -44,849 SYP  (44,625 bill + 224 fee)
  PEED:         +44,625 SYP  (bill amount)
  Beza Income:  +224 SYP     (0.5% commission)

Timing: ~3 seconds from confirm to receipt
```

### Syriatel Postpaid Mobile — Real Example
```
Customer: Ahmad Khaled (same user)
Customer ID: 0933-123456
Biller: Syriatel (سيريتل)
Interface: Syriatel B2B API

Step 1: Fetch Bill
  Request:  POST https://api.syriatel.sy/beza/v1/bill/inquiry
            { "msisdn": "0933123456" }
  Response: {
    "subscriber": "أحمد خالد",
    "plan": "Liberty Postpaid 25K",
    "monthly_subscription": 25000,
    "extra_usage": 3200,        // Data overage
    "international_calls": 1800,
    "vat_10%": 3000,
    "total": 33000,
    "due_date": "2026-06-12",
    "suspension_date": "2026-06-20",
    "status": "unpaid",
    "biller_ref": "SYR-BILL-345678"
  }

Step 2: Calculate Fee
  Syriatel commission: 1% × 33,000 = 330 + 100 SYP fixed = 430 SYP
  Total: 33,000 + 430 = 33,430 SYP

Step 3-5: Same flow as PEED but against Syriatel API

Amount Flow:
  User Wallet:  -33,430 SYP  (33,000 bill + 430 fee)
  Syriatel:     +33,000 SYP
  Beza Income:  +430 SYP     (1% + 100 SYP fixed)
```

## Flow 2: CSV Batch Billing (Government Fees)

### Government Fees — Real Example
```
Biller: Civil Affairs (الأحوال المدنية)
Interface: CSV batch file received daily from government portal

Step 1: CSV File Reception (03:00 AM daily)
  File: fees_2026-06-10.csv
  Source: FTP from csv.gateway.gov.sy/fees/
  Format:
    national_id,fee_type,amount,reference,ministry
    1234567890123456,قيد فردي,5000,REF-12345,العدل
    1234567890123456,إخراج قيد عائلي,3000,REF-12346,العدل
    2345678901234567,جواز سفر,25000,REF-12347,الداخلية
    3456789012345678,تصديق,2000,REF-12348,الخارجية

Step 2: System Processes CSV (03:00-04:00 AM)
  a. Validate format and required fields
  b. Parse 12,500 records
  c. Store in csv_billable_items table
  d. Match customer IDs (national IDs) to registered Beza users
  e. For matched users: send notification
     "لديك رسوم حكومية مستحقة: 8,000 ل.س — ادفع الآن عبر Beza"

Step 3: User Pays via App
  User enters national ID: 1234567890123456
  System queries csv_billable_items WHERE status = 'pending'
  Shows pending items:
    - قيد فردي (Civil status record): 5,000 SYP
    - إخراج قيد عائلي (Family registry): 3,000 SYP
    Total: 8,000 SYP
    Reference: CSV-BATCH-2026-06-10-15

Step 4: Pay and Confirm
  Execute payment from wallet
  Mark csv_billable_items as paid
  Link to bill_transaction record
  Generate receipt with government reference numbers

Amount Flow:
  User Wallet:  -8,120 SYP   (8,000 fees + 120 fee)
  Government:   +8,000 SYP
  Beza Income:  +120 SYP     (1.5% commission)

CSV Lifecycle:
  Uploaded → Processing → Ready → (user pays) → Completed
              ↓                     ↓
           Failed ←→ items    items marked paid
```

## Flow 3: Scheduled Bill Reminder + Auto-pay

### Monthly Electricity Auto-pay — Real Example
```
User Settings:
  Biller: PEED (الشركة العامة للكهرباء)
  Customer ID: 1234-5678-9012-3456-7890
  Schedule: Monthly (due around 15th of each month)
  Reminder: 3 days before due
  Auto-pay: Enabled
  Wallet: SYP — maintain minimum 50,000 SYP

Timeline:
  June 12 (3 days before due):
    06:00 — Reminder trigger
    06:01 — Push notification:
            "تذكير: فاتورة الكهرباء مستحقة خلال 3 أيام — سيتم الدفع تلقائياً"
    06:01 — SMS sent (if enabled)

  June 15 (Due date, 08:00):
    Step 1: Scheduler triggers auto-pay
    Step 2: Fetch bill from PEED
      → Bill: 46,200 SYP (different from last month)
    Step 3: Check wallet balance
      → Available: 98,000 SYP
      → Required: 46,200 + 231 (0.5% fee) = 46,431 SYP
      → Sufficient ✓
    Step 4: Execute payment
    Step 5: Update schedule:
      → next_due = July 15
      → auto_pay_status = 'active'
    Step 6: Notifications:
      → "تم دفع فاتورة الكهرباء تلقائياً: 46,431 ل.س"
      → Receipt: "BILL-PEED-20260615-AUTOPAY-XXXXX"

Auto-pay Failure Scenario:
  Step 3: Check wallet balance
    → Available: 30,000 SYP
    → Required: 46,431 SYP
    → Insufficient ✗
  Step 4: Record failure (failure_count = 1)
  Step 5: Retry schedule: +4 hours (12:00), +4 hours (16:00), +8 hours (00:00)
  Step 6: After 3 failures:
    → Pause auto-pay
    → Notification: "تعذر الدفع التلقائي لفاتورة الكهرباء بعد 3 محاولات — يرجى شحن المحفظة والدفع يدوياً"
    → Mark schedule: auto_pay_status = 'failed'

Monthly Flow Summary:
  May 15: Last auto-pay (42,500 + 213 = 42,713 SYP)
  Jun 12: Reminder sent
  Jun 15: Auto-pay (46,200 + 231 = 46,431 SYP)
  Jul 12: Reminder sent
  Jul 15: Auto-pay (estimated 44,000 + 220 = 44,220 SYP)
  ...
```
