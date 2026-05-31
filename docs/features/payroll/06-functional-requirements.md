# 06 — Functional Requirements

---

## FR-01: Company Registration
- **FR-01.1** Company submits: name (Arabic + English), commercial license number, tax ID, authorized signatory details, contact email, phone
- **FR-01.2** Beza admin reviews documents (uploaded PDFs of license, ID)
- **FR-01.3** On approval, system creates company record + dedicated payroll account (virtual ledger)
- **FR-01.4** Company receives API keys for direct integration (if requested)

## FR-02: Employee Management
- **FR-02.1** Company can add employees: full name (Arabic), employee ID, department, role, monthly salary amount, currency, phone number
- **FR-02.2** System checks phone number against existing Beza users
  - If user exists → link to that wallet
  - If user does not exist → create pending wallet (activated when user first logs in)
- **FR-02.3** Employees can be marked active / terminated
- **FR-02.4** Terminated employees cannot receive salary payments

## FR-03: CSV Upload & Validation
- **FR-03.1** Required columns: `employee_id`, `full_name_ar`, `amount`, `currency`, `department` (optional)
- **FR-03.2** Validation rules:
  - All employee_ids must exist in company roster
  - Amount must be positive number ≤ employee's registered salary ± 20 % tolerance
  - Currencies must match company's allowed currencies
- **FR-03.3** On validation failure, return row-level errors with Arabic messages
- **FR-03.4** Max file size: 5 MB (approx 10,000 employees)

## FR-04: Batch Processing
- **FR-04.1** Prerequisites: company balance ≥ total_amount + total_fee
- **FR-04.2** System places hold (CFE hold) on company account for total_amount + estimated fees
- **FR-04.3** Process flow:
  - Iterate employees → for each: credit employee Beza wallet, record transaction
  - If any single employee fails, continue batch (partial success)
- **FR-04.4** On batch completion: release remaining hold, settle fees
- **FR-04.5** Batch status values: `pending`, `processing`, `completed`, `partial_failure`, `failed`

## FR-05: Payslip Generation
- **FR-05.1** PDF generated per employee per batch
- **FR-05.2** Content (Arabic): company name/logo, employee name, month, basic salary, allowances, deductions, net pay, payment date, batch reference
- **FR-05.3** PDF is digitally signed (Beza seal) to prevent forgery
- **FR-05.4** Available for download by both company and employee

## FR-06: Retry Mechanism
- **FR-06.1** Automatic retry: 3 attempts — 5 min, 30 min, 2 hours
- **FR-06.2** After 3 failures: transaction marked `failed_permanent`
- **FR-06.3** Company HR can manual retry from dashboard
- **FR-06.4** Manual retry resets retry_count to 0

## FR-07: Settlement
- **FR-07.1** Pre-funded: Company deposits using bank transfer → Beza credits the payroll ledger → balance visible in dashboard
- **FR-07.2** T+1 (trusted companies): Beza fronts the payment; company settles next business day
- **FR-07.3** Settlement period configurable per company: T+0, T+1, T+3

## FR-08: Reconciliation Report
- **FR-08.1** Daily report: all batches, totals, fees, settlements
- **FR-08.2** Monthly report: aggregate by company, department
- **FR-08.3** Export formats: CSV, PDF
