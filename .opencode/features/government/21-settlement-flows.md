# Government Collections Settlement Flows

## Settlement Timing by Ministry

| Ministry | Settlement Method | Frequency | Settlement Terms |
|----------|------------------|-----------|------------------|
| Ministry of Finance | Wire transfer | Daily (T+1) | Next business day by 15:00 |
| Ministry of Interior | Batch API | Daily (T+0) | Same day batch at 20:00 |
| Traffic Directorate | File-based | Weekly (T+3) | Every Sunday |
| Damascus University | API transfer | Daily (T+0) | Real-time per transaction |
| Aleppo University | API transfer | Daily (T+0) | Real-time per transaction |
| Ministry of Justice | File-based | Monthly | 1st of month |
| Damascus Municipality | Portal + Wire | Weekly | Every Monday |
| Civil Registry | Batch API | Daily (T+1) | Next business day |

## Flow 1: Daily Batch Settlement to Ministry of Finance

### Step-by-Step
```
Step 1: Cut-off (23:59 daily)
  System identifies all completed MoF transactions
  Filter: status=completed, settlement_status=pending, biller=MOF
  Total: 5,250,000 SYP (20 transactions, 26,250 SYP fees retained)

Step 2: Generate Settlement Report
  ┌────────────────────────────────────┐
  │ تسوية يومية — وزارة المالية       │
  │ التاريخ: ١٥/٠٨/٢٠٢٥              │
  │                                    │
  │ عدد المعاملات: ٢٠                 │
  │ إجمالي المبلغ: ٥,٢٥٠,٠٠٠ ل.س     │
  │                                    │
  │ التفاصيل:                         │
  │ GOV-2025-0815-7823 → 262,500     │
  │ GOV-2025-0815-7826 → 150,000     │
  │ ...                              │
  │                                    │
  │ إجمالي العمولة المحتجزة: ٢٦,٢٥٠  │
  │ صافي المحول: ٥,٢٥٠,٠٠٠ ل.س      │
  └────────────────────────────────────┘

Step 3: Execute Settlement
  Option A: Wire Transfer
    → Beza Corporate Bank Account
    → Ministry of Finance Bank Account (at Central Bank of Syria)
    → Amount: 5,250,000 SYP
    → Reference: SETTLE-MOF-20250815

  Option B: Batch API (if supported)
    → POST /api/mof/settlement/batch
    → { transactions: [...], total: 5,250,000 }
    → MoF confirms receipt

Step 4: Update Records
  Each transaction: settlement_status = settled, settled_at = now()
  Reconciliation record created
  Notification sent to Ministry finance team

Step 5: Accounting
  DR: Ministry Payable — MoF    5,250,000
  CR: Bank Account               5,250,000
  (Fee already booked at collection time)
```

## Flow 2: Real-Time Settlement (University Tuition)

### Step-by-Step
```
Step 1: Payment Completed
  Student pays 240,000 SYP tuition to Damascus University

Step 2: Split Settlement
  Beza fee (0.25%): 600 SYP → Beza Income
  University amount: 239,400 SYP → Beza Settlement Pool
  OR full 240,000 to university and Beza bills separately

Step 3: Notify University (Real-time)
  POST /api/damascus-university/tuition/confirm
  { studentId: "2024123456", amount: 240,000, ref: "GOV-2025-0815-7900" }

Step 4: Execute Transfer
  If university has API-level settlement:
    → API confirms payment is recorded in university system
    → Batch settlement at end of day
  If university has direct bank integration:
    → Individual wire for each payment (less common)
```

## Flow 3: File-Based Settlement (Traffic Directorate)

### Step-by-Step
```
Step 1: Weekly Cut-off (Saturday 23:59)
  Weekly traffic fine collections: 450,000 SYP (60 fines)

Step 2: Generate File
  Format: CSV with SHA-256 checksum
  Fields: fine_id, plate, amount, beza_ref, payment_date, status
  File: /settlements/traf/TRAF-SETTLE-2025-W33.csv

Step 3: Upload to Ministry Portal
  SFTP or shared drive: //mof.sy/settlements/traffic/
  Encrypted with ministry public key

Step 4: Ministry Processes File
  Traffic directorate reconciles file against their records
  Confirms discrepancies within 3 business days
  Wire transfer initiated by ministry (not Beza)

Step 5: Reconciliation
  Beza reconciles ministry confirmation against sent file
  Any discrepancies flagged for investigation
```

## Settlement Fee Structure

| Component | Rate | Collected From | Timing |
|-----------|------|----------------|--------|
| Beza transaction fee | 0.25%–1.5% | User | At payment time |
| Ministry settlement fee | 0%–0.5% of volume | Ministry | Monthly invoice |
| Late payment penalty | 2%–5% of amount | User | Added to total |
| Early payment discount | 50% of penalty waived | User (saves) | Applied at payment |
| Refund processing fee | 1,000 SYP flat | User (if applicable) | On refund |

## Settlement Reconciliation Check

```
Daily Settlement Check:

1. Verify: SUM(amount) FROM government_transactions
   WHERE biller_id = MOF AND settlement_status = 'pending'
   AND created_at BETWEEN '2025-08-15 00:00' AND '2025-08-15 23:59'
   → Expected settlement amount: 5,250,000

2. Verify: MINISTRY_TOTAL = BEZA_TOTAL
   → If match: proceed with settlement
   → If mismatch: flag, do not settle, investigate

3. Post-settlement verification:
   → Ministry confirms receipt of 5,250,000 SYP
   → Update all as settled
   → Generate reconciliation report
```
