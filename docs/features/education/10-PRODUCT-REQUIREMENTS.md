# 10 — Product Requirements Document (PRD)

## 10.1 Parent Payment Flow (FP-01)

**Story**: As a parent, I want to pay my child's school fees using Beza wallet so that I don't have to queue at school with cash.

**Acceptance Criteria**:
1. User sees all registered children's schools on Education home screen
2. Each school card shows: school name, fee due amount, due date, status (paid/pending/overdue)
3. Tapping a school shows itemised invoice (fee structure breakdown)
4. User selects full or partial payment (school must allow partial)
5. Payment source: Beza wallet balance, linked bank card, saved cards
6. Fee calculations update in real-time (no late fees before due date)
7. Authentication: fingerprint/Face ID + 6-digit PIN
8. Success screen shows: amount paid, receipt reference, timestamp, QR code
9. Receipt auto-saves to device and uploads to cloud
10. Failure: show clear error, retry option, support contact

**Edge Cases**:
- Insufficient balance → suggest top-up from bank card
- Payment during school-outside-office-hours → still processed, receipt valid
- Duplicate payment → automatic refund within 24h with notification
- Network interrupted mid-payment → idempotency key, resumable
- School changed fees after invoice generated → reconcile on confirmation

## 10.2 School Dashboard — Dashboard View (FS-01)

**Story**: As a school finance manager, I want a real-time dashboard showing fee collection status so that I can track who has paid without manual reconciliation.

**Acceptance Criteria**:
1. Summary cards: Total enrolled, Total fee due, Total collected, Collection rate %
2. Trend chart: daily/weekly/monthly collection amounts
3. Filterable student list: by grade, section, payment status (paid/partial/overdue)
4. Each student row: name, ID, grade, fee amount, paid amount, balance, last payment date
5. Pagination (50 per page) + search by name/ID
6. Export button: CSV, PDF, Excel
7. Real-time updates: when parent pays, status changes within 5 seconds
8. Sortable columns

## 10.3 Fee Template Builder (FS-03)

**Story**: As a school admin, I want to define fee structures per grade/year so that invoices are generated automatically each term.

**Acceptance Criteria**:
1. Create fee template: name, term, academic year, grade(s)
2. Line items: tuition, registration, books, activities, uniform, transport, other (custom)
3. Each line item: amount (SYP), optional description, mandatory/optional flag
4. Discount types: sibling discount (%), early-bird discount (fixed), scholarship (fixed)
5. Late fee: percentage of unpaid amount after due date + max cap
6. Payment terms: full upfront, 2 instalments, 3 instalments, monthly
7. Instalment schedule: define due dates for each instalment
8. Template versioning: save as draft → publish; changes apply from next term

## 10.4 Bulk Reminder (FS-05)

**Story**: As a school finance manager, I want to send reminders to all overdue parents at once to reduce manual follow-up.

**Acceptance Criteria**:
1. Filter overdue list: by term, by days overdue (7/14/30/60+)
2. Select all / individual parents
3. Choose channel: WhatsApp, SMS, both
4. Preview message with template variables: {{parent_name}}, {{student_name}}, {{amount_due}}, {{due_date}}, {{late_fee}}
5. Send → confirmation with delivery report
6. Failed delivery numbers shown for manual action
7. Opt-out handling: respect recipient preferences
8. Daily send limit to prevent spam classification

## 10.5 Financing Engine (FO-04)

**Story**: As a parent, I want to pay school fees in monthly instalments so that I can manage cash flow.

**Acceptance Criteria**:
1. Eligibility check: minimum wallet age 6 months, 10+ transactions, no defaults
2. Offer calculation: total fee amount, interest rate (8-12%), term (3/6/9/12 months)
3. Parent accepts → Beza pays school in full immediately
4. Parent sees repayment schedule: monthly amount × term
5. Auto-debit from wallet on scheduled dates
6. Grace period: 5 days late before penalty (5% of installment)
7. Early settlement: full remaining principal, no interest rebate
8. Default: after 60 days, sent to collections, parent locked from further financing
