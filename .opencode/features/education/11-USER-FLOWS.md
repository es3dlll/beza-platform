# 11 — User Flows

## 11.1 Flow: Parent Pays School Fee

```
[Education Home] → Select Child/School
     │
     ▼
[Fee Detail Screen]
  - School name, term, academic year
  - Itemised breakdown: tuition, books, activities
  - Total due, paid, balance
  - Due date
     │
     ├── [Pay Now] → [Payment Method Selection]
     │                    ├── Beza Wallet Balance: 1,200,000 SYP
     │                    ├── Visa/Mastercard (linked)
     │                    ├── Add new card
     │                    └── [Pay Full / Pay Partial]
     │                           │
     │                           ▼
     │                    [Confirm Payment]
     │                    - Amount: 880,000 SYP
     │                    - Fees: 0 (covered by school)
     │                    - Total charge: 880,000 SYP
     │                           │
     │                           ▼
     │                    [Authenticate]
     │                    - Fingerprint / Face ID
     │                    - Enter 6-digit PIN
     │                           │
     │                           ▼
     │                    [Processing...] (spinner, max 10s)
     │                           │
     │                    Success ────────────── Failure
     │                    │                        │
     │                    ▼                        ▼
     │              [Receipt Screen]        [Error Screen]
     │              - Reference: BEZ-EDU-     - Reason
     │                2026-05-15-XX-XXXX      - Retry button
     │              - Amount: 880,000 SYP     - Support chat
     │              - Timestamp               - Alternative method
     │              - QR code
     │              - Download PDF
     │              - Share button
     │
     └── [Schedule Auto-Pay] → [Set Date] → [Confirm]
```

## 11.2 Flow: School Creates Fee Template

```
[Dashboard Login] → [Fee Management]
     │
     ▼
[Fee Templates] → [+ Create Template]
     │
     ▼
[Template Builder]
  - Name: "Grade 10 — Term 1 — 2025/2026"
  - Academic Year: 2025/2026
  - Term: Term 1
  - Grade: Grade 10 | Section: A, B, C
     │
     ▼
[Fee Line Items]
  + Tuition: 750,000 SYP (mandatory)
  + Books: 120,000 SYP (mandatory)
  + Activities: 50,000 SYP (optional)
  + Registration: 25,000 SYP (once per year)
  + Transport: 100,000 SYP (optional)
     │
     ▼
[Discount Configuration]
  - Sibling: 10% on tuition for 2nd+ child
  - Early-bird: 50,000 off if paid before 1 Sep
  - Scholarship: custom amount per student
     │
     ▼
[Late Fee Rules]
  - 2% per month on unpaid balance
  - Max late fee: 10% of total fee
  - Late fee waiver period: 7 days after due date
     │
     ▼
[Payment Schedule]
  - Full payment due: 1 Sep 2025
  - Instalment plan available: 2/3 terms
     │
     ▼
[Save as Draft] → [Preview Invoice] → [Publish]
     │
     ▼
[System generates invoices for all Grade 10 students]
[Notifications sent to Grade 10 parents]
```

## 11.3 Flow: School Onboarding

```
[Beza Admin] → [Merchant Onboarding]
     │
     ▼
[School Registration Form]
  - Name (Arabic + English)
  - Type: Private/Public/University/Tutoring
  - Licence number + document upload
  - Address: governorate, city, district
  - Principal name, phone, email
  - Finance manager contact
  - Bank account details (for settlement)
     │
     ▼
[KYC Verification]
  - Document verification (licence, tax registration)
  - Physical visit (for P0 schools)
  - Approval workflow: Sales → Compliance → Ops
     │
     ▼
[Onboarding Wizard]
  1. Add staff accounts (finance, admin)
  2. Upload student roster (CSV or manual)
  3. Create fee templates
  4. Configure notification preferences
  5. Integration setup (API key for ERP)
  6. QR code generation for enrolment day
  7. Test payment (1 SYP charge)
     │
     ▼
[Go Live]
  - School status: Active
  - Parents receive invitation via WhatsApp
  - First 50 payments free to incentivise adoption
```
