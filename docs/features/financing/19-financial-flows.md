# التدفقات المالية — Financial Flows

---

## Flow 1: Qard Hasan (قرض حسن)

### End-to-End Flow
```
User                          Beza Platform                      User Wallet
 │                               │                                  │
 │  1. Submit application        │                                  │
 │ ───────────────────────────►  │                                  │
 │                               │  2. Validate eligibility         │
 │                               │  3. Check credit score          │
 │                               │  4. Verify guarantor            │
 │                               │                                  │
 │  5. Receive approval          │                                  │
 │ ◄───────────────────────────  │                                  │
 │                               │                                  │
 │  6. Accept offer & e-sign     │                                  │
 │ ───────────────────────────►  │                                  │
 │                               │  7. Generate contract            │
 │                               │  8. Debit financing pool         │
 │                               │  9. Credit user wallet           │
 │                               │ ───────────────────────────────► │
 │                               │                                  │
 │  10. Receive funds            │                                  │
 │ ◄───────────────────────────  │ ◄─────────────────────────────── │
 │                               │                                  │
 │  === REPAYMENT PHASE ===     │                                  │
 │                               │                                  │
 │  11. Auto-deduct daily        │                                  │
 │                               │ ◄─────────────────────────────── │
 │                               │  (daily 08:00, 90 days)         │
 │                               │                                  │
 │  12. Mark installment paid    │                                  │
 │                               │                                  │
 │  13. Final payment            │                                  │
 │                               │  (after 90th payment)           │
 │  14. Loan completed           │                                  │
 │ ◄───────────────────────────  │                                  │
 │                               │                                  │
 │  15. Credit score +30 pts     │                                  │
```

### Accounting Entries
```
At Disbursement:
  Dr. Financing Receivable (User)     SYP 300,000
  Cr. Beza Financing Pool             SYP 300,000

  Dr. Admin Fee Receivable            SYP 3,000
  Cr. Admin Fee Income                SYP 3,000

At Each Repayment (daily SYP 3,333):
  Dr. User Wallet                     SYP 3,333
  Cr. Financing Receivable (User)     SYP 3,333

At Late Fee (per day):
  Dr. User Wallet                     SYP 5,000
  Cr. Charity Liability Account       SYP 5,000

At Completion:
  Financing Receivable = 0
  Contract Status = 'completed'
```

### Timing Diagram
```
Day 0:  Application → Score → Approve → Disburse (2 hours)
Day 1-7: Grace period (no deductions)
Day 8:   First auto-deduction (SYP 3,333)
Day 9-97: Daily deductions continue
Day 97:  Final deduction (SYP 3,333)
Day 97:  Contract completed
```

---

## Flow 2: Murabaha (مرابحة)

### End-to-End Flow
```
User                    Beza Platform              Supplier/Merchant
 │                           │                          │
 │  1. Select product        │                          │
 │ ───────────────────────►  │                          │
 │                           │                          │
 │  2. Submit application    │                          │
 │  3. Verification & score  │                          │
 │  4. Approve offer         │                          │
 │                           │                          │
 │  5. User pays down payment│                          │
 │   (10-20%)                │                          │
 │ ───────────────────────►  │                          │
 │                           │                          │
 │  === BIZA PURCHASES ===  │                          │
 │                           │  6. Beza buys from       │
 │                           │     supplier             │
 │                           │ ───────────────────────► │
 │                           │ ◄─────────────────────── │
 │                           │     (item delivered to   │
 │                           │      Beza logistics)     │
 │                           │                          │
 │  === BIZA SELLS TO USER ===                         │
 │                           │                          │
 │  7. Contract: Cost+Profit │                          │
 │     Cost: SYP 2,500,000  │                          │
 │     Profit: SYP 200,000  │                          │
 │     Total: SYP 2,700,000 │                          │
 │ ◄─────────────────────────  │                        │
 │                           │                          │
 │  8. E-sign contract       │                          │
 │ ───────────────────────►  │                          │
 │                           │                          │
 │  9. Item delivered to user│                          │
 │     (ownership still Beza)│                          │
 │ ◄─────────────────────────  │                        │
 │                           │                          │
 │  === INSTALLMENTS ===    │                          │
 │  10. Monthly payments     │                          │
 │     SYP 225,000 × 12     │                          │
 │ ◄─────────────────────────  │                        │
 │                           │                          │
 │  11. After 12 payments:   │                          │
 │      Ownership transferred│                          │
 │      to user              │                          │
 │ ◄─────────────────────────  │                        │
```

### Accounting Entries
```
Step 1 — Down Payment:
  Dr. User Wallet                     SYP 250,000 (10%)
  Cr. Down Payment Liability          SYP 250,000

Step 2 — Beza Purchases from Supplier:
  Dr. Inventory (Item)                SYP 2,500,000
  Cr. Beza Financing Pool             SYP 2,500,000

Step 3 — Beza Sells to User:
  Dr. Financing Receivable (User)     SYP 2,700,000
  Cr. Inventory (Item)                SYP 2,500,000
  Cr. Deferred Profit                 SYP 200,000
  Dr. Down Payment Liability          SYP 250,000
  Cr. Financing Receivable (User)     SYP 250,000

Step 4 — Each Installment (SYP 225,000 × 12):
  Dr. User Wallet                     SYP 225,000
  Cr. Financing Receivable (User)     SYP X (principal portion)
  Cr. Deferred Profit                 SYP Y (profit portion, decreasing)
  (Profit recognized as earned over time)

Step 5 — At Full Payment:
  Ownership transferred to user
  Inventory liability = 0
```

### Profit Recognition
```
Month   Principal   Profit   Total   Remaining
  1      191,667    33,333  225,000  2,475,000
  2      193,333    31,667  225,000  2,250,000
  3      195,000    30,000  225,000  2,025,000
  4      196,667    28,333  225,000  1,800,000
  5      198,333    26,667  225,000  1,575,000
  6      200,000    25,000  225,000  1,350,000
  7      201,667    23,333  225,000  1,125,000
  8      203,333    21,667  225,000    900,000
  9      205,000    20,000  225,000    675,000
 10      206,667    18,333  225,000    450,000
 11      208,333    16,667  225,000    225,000
 12      210,000    15,000  225,000          0
        2,400,000  300,000 2,700,000
```

---

## Flow 3: Micro-Enterprise Financing (تمويل المنشآت الصغيرة)

### End-to-End Flow
```
User (Micro-entrepreneur)         Beza Platform              Supplier
 │                                     │                        │
 │  1. Pre-qualification check          │                        │
 │     (wallet data analysis)           │                        │
 │ ◄──────────────────────────────────  │                        │
 │                                     │                        │
 │  2. Full application + docs         │                        │
 │ ─────────────────────────────────►  │                        │
 │                                     │                        │
 │  3. Cash flow analysis              │                        │
 │     - Daily sales from wallet       │                        │
 │     - Expense patterns              │                        │
 │     - Revenue trends (12 months)    │                        │
 │     - Seasonal adjustments          │                        │
 │                                     │                        │
 │  4. Credit committee (if > 3M)      │                        │
 │     OR auto-approve                 │                        │
 │                                     │                        │
 │  5. Contract (Murabaha for assets,  │                        │
 │     Qard Hasan for working capital) │                        │
 │                                     │                        │
 │  6A. Asset purchase: disbursement   │                        │
 │      to supplier                    │                        │
 │      (Murabaha structure)           │ ───────────────────►   │
 │                                     │                        │
 │  OR 6B. Working capital:            │                        │
 │         disbursement to wallet      │                        │
 │                                     │                        │
 │  7. Flexible repayment begins       │                        │
 │     (minimum SYP 200,000/month)     │                        │
 │     Auto-deduct from daily sales    │                        │
 │ ◄──────────────────────────────────  │                        │
 │                                     │                        │
 │  8. Quarterly cash flow review      │                        │
 │     - Adjust installment if needed  │                        │
 │     - Revenue decline → restructure │                        │
 │     - Revenue growth → early payoff │                        │
 │                                     │                        │
 │  9. Completion → new facility       │                        │
 │     (up to SYP 10,000,000 next)     │                        │
```

### Revenue-Linked Repayment
```
Month   Projected Revenue   Min Payment   Actual Payment
  1      SYP 1,500,000      SYP 200,000   SYP 200,000
  2      SYP 1,500,000      SYP 200,000   SYP 300,000 (extra)
  3      SYP 1,500,000      SYP 200,000   SYP 200,000
  4      SYP 1,200,000      SYP 200,000   SYP 150,000 (restructured)
  5      SYP 1,200,000      SYP 150,000   SYP 150,000
  ...
```

### Provisioning Schedule
```
Days Past Due   Provision Rate   Accounting Entry
   0-30             0%           No provision
  31-60            25%           Dr. Provision Expense / Cr. Allowance for Losses
  61-90            50%           Dr. Provision Expense / Cr. Allowance for Losses
  91-120           75%           Additional provision
  121+            100%           Full provision + write-off consideration
```
