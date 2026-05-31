# 05 — Journey Maps

---

## Journey 1: HR Manager — Full Payroll Cycle

| Step | Action | System Response | Emotions |
|------|--------|----------------|----------|
| 1 | Logs into Beza Company Dashboard | Shows dashboard with balance, previous batches | Neutral |
| 2 | Navigates to "Payroll → New Batch" | Shows upload screen + template download link | Positive |
| 3 | Downloads CSV template | Returns template with required columns | Neutral |
| 4 | Fills data from Excel, saves CSV | — | Neutral (routine) |
| 5 | Uploads CSV file | Validates: shows employee count, total amount, fee | Positive |
| 6 | (Optional) Schedules future date | Calendar picker | Neutral |
| 7 | Confirms batch with PIN | Processing modal with progress bar (per employee) | Anxious |
| 8 | Receives success notification | "تمت معالجة الدفعة بنجاح" — Batch summary screen | Very positive |
| 9 | Reviews failed (if any) | Shows failed employees with reason | Negative (mitigated) |
| 10 | Downloads payslips ZIP | Generates individual PDFs, wraps in ZIP | Positive |
| 11 | Logs out | — | Satisfied |

---

## Journey 2: Employee — Receiving Salary

| Step | Action | System Response | Emotions |
|------|--------|----------------|----------|
| 1 | Phone receives push notification | "تم إيداع راتبك: 1,200,000 ل.س" | Positive, relieved |
| 2 | Opens Beza app | App loads; wallet balance updated | Happy |
| 3 | Taps notification | Opens payroll detail: amount, date, company | Happy |
| 4 | Views payslip | Shows breakdown: basic, allowances, deductions, net | Satisfied |
| 5 | Downloads / shares payslip | PDF saved to device or shared via WhatsApp | Satisfied |
| 6 | Transfers part of salary to family | Beza P2P transfer | Empowered |

---

## Journey 3: Failed Transaction — Resolution Cycle

| Step | Actor | Action | System |
|------|-------|--------|--------|
| 1 | System | Batch processes | Detects employee wallet not activated |
| 2 | System | Marks transaction "failed" | Sets status=failed, failure_reason="wallet_not_active" |
| 3 | System | Attempts retry #1 (5 min later) | Same result |
| 4 | System | Attempts retry #2 (30 min later) | Same result |
| 5 | System | Attempts retry #3 (2 hours later) | Same result |
| 6 | System | Sends notification to company HR | "3 محاولات فاشلة: الموظف أحمد علي" |
| 7 | HR | Contacts employee | Helps them activate Beza wallet |
| 8 | HR | Logs in, retries failed item | System processes successfully |
| 9 | Employee | Receives salary | Happy resolution |
