# 08 — Feature Inventory

## 8.1 Parent App Features

| ID | Feature | Priority | Description |
|---|---|---|---|
| FP-01 | School fee payment | P0 | Pay termly/annual fees from wallet/card |
| FP-02 | Fee breakdown view | P0 | See itemised invoice (tuition, books, activities) |
| FP-03 | Payment history | P0 | Full transaction log with receipt download |
| FP-04 | Multi-child dashboard | P0 | All children across different schools/unis in one view |
| FP-05 | Push/SMS reminders | P0 | Receive fee deadline alerts |
| FP-06 | Auto-pay scheduling | P1 | Set recurring payment dates |
| FP-07 | Instalment plan sign-up | P1 | Apply for fee financing |
| FP-08 | Digital receipt | P0 | PDF + QR code, university-registrar-ready |
| FP-09 | Diaspora payment (FX) | P1 | Pay in EUR/USD/SAR for child in Syria |
| FP-10 | Dispute a fee | P2 | Flag incorrect charges to school |
| FP-11 | Download tax certificate | P2 | Annual statement for employer reimbursement |
| FP-12 | Share payment link | P1 | Send link to relative to pay on your behalf |
| FP-13 | Attendance-linked fees | P2 | Tutoring: pay per session attended |

## 8.2 School Dashboard Features

| ID | Feature | Priority | Description |
|---|---|---|---|
| FS-01 | Fee management dashboard | P0 | Real-time view of payments due/collected |
| FS-02 | Student enrolment list | P0 | Import/manage student roster |
| FS-03 | Fee template builder | P0 | Define fee structures per grade/year/programme |
| FS-04 | Auto-invoicing | P0 | Generate invoices per term/semester |
| FS-05 | Bulk reminder (WhatsApp/SMS) | P0 | One-click reminders to all overdue parents |
| FS-06 | Receipt bulk download | P1 | Download all receipts for audit |
| FS-07 | Partial payment recording | P1 | Record partial cash payments collected offline |
| FS-08 | CSV/Excel export | P0 | Export financial data for accounting |
| FS-09 | Multi-faculty/branch management | P1 | Separate views per faculty/campus |
| FS-10 | Staff management | P2 | Role-based access (finance, admin, principal) |
| FS-11 | Fee change notification | P1 | Announce fee changes with parent opt-out window |
| FS-12 | API for ERP integration | P1 | Connect to existing SIS/ERP |
| FS-13 | Enrolment QR generation | P1 | Generate per-student QR for enrolment day |
| FS-14 | Deposit/settlement report | P1 | Daily/monthly settlement to school bank account |

## 8.3 Platform/Shared Features

| ID | Feature | Priority | Description |
|---|---|---|---|
| FO-01 | Merchant onboarding (school) | P0 | KYC, bank account, fee structure setup |
| FO-02 | Multi-currency settlement | P1 | Schools receive in SYP or USD |
| FO-03 | Reconciliation engine | P0 | Match payments to invoices in real-time |
| FO-04 | Financing engine | P1 | Parent credit scoring + 3rd-party financing |
| FO-05 | Notification engine | P0 | WhatsApp, SMS, push, email |
| FO-06 | Reporting & analytics | P1 | Dashboards for Beza ops, schools |
| FO-07 | Audit log | P0 | All financial actions logged immutably |
| FO-08 | School directory | P2 | Public listing of Beza-enabled schools |
