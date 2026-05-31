# Government Collections Financial Flows

## Flow 1: Tax Payment

### Step-by-Step
```
User enters Tax ID → system queries ministry → shows amount due → user pays → Beza settles to ministry

Step 1: User Initiates
  User opens Beza → Government Hub → Tax
  Tax ID field: 2536894751
  Tax type: Income Tax

Step 2: Query Ministry of Finance (MoF)
  Beza → MoF API: GET /api/tax/query?taxId=2536894751&year=2025
  MoF ← Response: {
    taxpayer_name: "أحمد محمد",
    obligations: [{ year: 2025, base: 250,000, penalty: 12,500, total: 262,500 }],
    total_due: 262,500
  }

Step 3: Display to User
  Amount: 262,500 SYP (base: 250,000 + penalty: 12,500)
  Beza fee: 1,312 SYP (0.5%)
  Total to pay: 263,812 SYP
  User sees: deadline, overdue status, breakdown

Step 4: User Confirms Payment
  User taps "تأكيد الدفع"
  PIN entry: ****

Step 5: Debit from Beza Wallet
  Account: User Main Wallet (SYP)
  DR: 263,812 SYP (total charged)
  Balance: 500,000 → 236,188 SYP

Step 6: Notify Ministry of Payment
  Beza → MoF API: POST /api/tax/confirm
  {
    taxId: "2536894751",
    amount: 262,500,
    bezaReference: "GOV-2025-0815-7823",
    timestamp: "2025-08-15T10:23:45Z"
  }
  MoF ← Response: { confirmation: "MOF-CONF-7823", status: "recorded" }

Step 7: Generate Receipt
  Receipt ref: GOV-2025-0815-7823
  QR data: beza://verify?ref=GOV-2025-0815-7823&hash=...
  Store receipt record, generate PDF

Step 8: Settlement (End of Day)
  Beza aggregates all MoF payments for the day
  Total: 5,250,000 SYP (20 transactions)
  Beza → MoF: Wire transfer 5,250,000 SYP (or batch API settlement)
  Beza retains fees: 26,250 SYP (0.5% × 5,250,000)
  Settlement status: completed

Step 9: User Receives Confirmation
  Push: "✅ تم دفع 263,812 ل.س ضريبة الدخل. المرجع: GOV-2025-0815-7823"
  SMS: same (if enabled)
```

### Sequence Diagram
```
User            Beza App          Beza Backend        MoF API          CFE Wallet
 │                │                   │                 │                │
 │── Tax ID ──────>                   │                 │                │
 │                │── queryTax() ─────>                 │                │
 │                │                   │── GET /query ───>                │
 │                │                   │<── obligations ─│                │
 │                │<── display ────────│                 │                │
 │<── amount due ──│                   │                 │                │
 │                │                   │                 │                │
 │── confirm ─────>                   │                 │                │
 │── PIN ─────────>                   │                 │                │
 │                │── payTax() ───────>                 │                │
 │                │                   │── debit ─────────────────────────>│
 │                │                   │<── debited ──────────────────────│
 │                │                   │                 │                │
 │                │                   │── POST /confirm ─>               │
 │                │                   │<── confirmed ────│                │
 │                │                   │                 │                │
 │                │                   │── genReceipt() ──│                │
 │                │<── receipt ───────│                 │                │
 │<── receipt ────│                   │                 │                │
 │                │                   │                 │                │
 │                │                   │  [End of day]                    │
 │                │                   │── batch settle ─>                │
 │                │                   │<── settled ─────│                │
```

## Flow 2: Passport Fee Payment

### Step-by-Step
```
User enters application number → pays → Beza confirms to ministry → receipt generated

Step 1: User Initiates
  User opens Beza → Government Hub → Passport
  Application type: Renewal
  Application number: PPR-2025-7890123

Step 2: Query Ministry of Interior (MoI)
  Beza → MoI API: GET /api/passport/query?appNo=PPR-2025-7890123
  MoI ← Response: {
    applicant: "سامر أحمد",
    status: "approved",
    fees: { standard: 75,000, urgent: 125,000 },
    valid_until: "2025-12-31"
  }

Step 3: Display to User
  Applicant: سامر أحمد
  Status: Approved ✅
  Standard fee: 75,000 SYP (6 pages, 10 business days)
  Urgent fee: 125,000 SYP (2 business days)
  User selects: Standard (75,000 SYP)
  Beza fee: 375 SYP (0.5%)
  Total: 75,375 SYP

Step 4: User Confirms
  User taps "تأكيد الدفع"
  PIN: ****

Step 5: Debit from Wallet
  Account: User Main Wallet (SYP)
  DR: 75,375 SYP
  Balance: 236,188 → 160,813 SYP

Step 6: Notify Ministry of Interior
  Beza → MoI API: POST /api/passport/confirm
  {
    appNo: "PPR-2025-7890123",
    amount: 75,000,
    feeType: "standard",
    bezaReference: "GOV-2025-0815-7824",
    timestamp: "2025-08-15T11:00:00Z"
  }
  MoI ← Response: { confirmation: "MOI-CONF-456", status: "recorded" }

Step 7: Generate Receipt
  Receipt ref: GOV-2025-0815-7824
  Ministry reference: MOI-CONF-456
  QR generated, PDF saved

Step 8: Diaspora Share
  User taps "مشاركة"
  Shares PDF via email to Syrian embassy in Berlin
  Embassy verifies QR on MoI portal
  Passport printing initiated

Step 9: Settlement (End of Day)
  Beza aggregates all MoI payments
  Wire transfer to MoI account
  Beza retains 0.5% fee
```

### Sequence Diagram
```
User (Samer)    Beza App        Beza Backend       MoI API          CFE Wallet
 │                │                 │                 │                │
 │── appNo ───────>                 │                 │                │
 │                │── query() ──────>                 │                │
 │                │                 │── GET /query ───>                │
 │                │                 │<── fees ────────│                │
 │                │<── display ─────│                 │                │
 │<── fee options ─│                 │                 │                │
 │                │                 │                 │                │
 │── confirm ─────>                 │                 │                │
 │── PIN ─────────>                 │                 │                │
 │                │── pay() ────────>                 │                │
 │                │                 │── debit ────────────────────────>│
 │                │                 │<── debited ──────────────────────│
 │                │                 │                 │                │
 │                │                 │── POST /confirm ─>               │
 │                │                 │<── confirmed ────│                │
 │                │                 │                 │                │
 │                │                 │── genReceipt() ─│                │
 │                │<── receipt ─────│                 │                │
 │<── receipt ────│                 │                 │                │
 │                │                 │                 │                │
 │── share ───────>  (PDF to embassy)                 │                │
 │                │                 │  [End of day]   │                │
 │                │                 │── batch settle ─>                │
 │                │                 │<── settled ─────│                │
```

## Flow 3: Traffic Fine Payment (with Early Discount)

### Step-by-Step
```
Step 1: User queries by licence plate
  Plate: ۱۲۳٤٥٦ (123456)
  System returns: 1 fine — 15,000 SYP (speeding)
  Early discount: 50% if paid within 90 days
  Discounted: 7,500 SYP (valid until 2025-09-20)

Step 2: User selects "use early discount"
  Amount with discount: 7,500 SYP
  Beza fee (0.5%): 37 SYP
  Total: 7,537 SYP

Step 3: Payment
  Debit wallet: 7,537 SYP
  Notify Traffic Directorate
  Generate receipt: GOV-2025-0815-7825
```

## Flow 4: University Tuition Payment

### Step-by-Step
```
Step 1: Student queries by Student ID
  ID: 2024123456
  University: Damascus University
  Semester: Fall 2025-2026
  Fees: tuition 200,000 + registration 25,000 + faculty 15,000 = 240,000 SYP

Step 2: Payment from parent's wallet
  Parent (Ahmed) on family plan
  Option: "ادفع من محفظة الأب"
  Parent receives push: تأكيد دفع 240,000 ل.س رسوم جامعة؟
  Parent approves with PIN

Step 3: Payment
  Debit parent wallet: 240,600 SYP (240,000 + 600 fee)
  Notify university API
  University updates student registration status
  Receipt sent to both parent and student
```
