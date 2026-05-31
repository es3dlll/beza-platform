# 04 — Personas

---

## Persona 1: Layla Hassan — HR Manager

| Attribute | Detail |
|-----------|--------|
| **Age** | 34 |
| **Company** | Al-Sham Steel Industries, Homs (300 employees) |
| **Role** | HR Manager, 6 years at company |
| **Tech literacy** | Moderate — uses Excel daily, comfortable with web apps |
| **Pain point** | Spends 3 days every month counting cash packets with security guards |
| **Goal** | Complete payroll in < 2 hours with zero errors |
| **Arabic name** | ليلى حسن |

**Scenario:** Layla receives salary data from accounting as an Excel export. She copies columns into Beza's CSV template, uploads it, and reviews the batch summary. She confirms with her Beza PIN and the batch processes in 12 seconds. All 300 workers get SMS notifications instantly.

---

## Persona 2: Mahmoud Al-Khatib — CFO

| Attribute | Detail |
|-----------|--------|
| **Age** | 47 |
| **Company** | Damascus Pharma Group, Damascus (150 employees) |
| **Role** | CFO, CPA |
| **Tech literacy** | High — uses ERP (Oracle NetSuite) |
| **Pain point** | Cannot reconcile cash distributions; no audit trail |
| **Goal** | Automated reconciliation with general ledger |
| **Arabic name** | محمود الخطيب |

**Scenario:** Mahmoud deposits SYP 250,000,000 into Beza's payroll account via bank transfer. He logs in, sees the balance reflected. After payroll runs, he exports a settlement report and matches it against his ERP entries.

---

## Persona 3: Ahmad Ali — Factory Worker

| Attribute | Detail |
|-----------|--------|
| **Age** | 28 |
| **Company** | Al-Sham Steel Industries, Homs |
| **Role** | Welder (monthly salary: SYP 1,200,000) |
| **Tech literacy** | Low — owns a smartphone, uses WhatsApp |
| **Pain point** | Walking 30 minutes to the nearest ATM; queue; cash theft risk |
| **Goal** | Get salary instantly on phone, pay bills from wallet |
| **Arabic name** | أحمد علي |

**Scenario:** Ahmad hears his phone ping. Opens Beza app. "تم إيداع راتبك: 1,200,000 ل.س" (Your salary has been deposited: SYP 1,200,000). He views his payslip, sees deductions. Transfers rent money to his landlord via Beza.

---

## Persona 4: Ibrahim Suleiman — Beza Admin / Ops

| Attribute | Detail |
|-----------|--------|
| **Age** | 31 |
| **Organization** | Beza Operations Team |
| **Role** | Payments Ops Lead |
| **Tech literacy** | Expert — SQL, monitoring dashboards |
| **Pain point** | Manual intervention on failed batches is slow |
| **Goal** | Automated retry + clear dashboards for exceptions |
| **Arabic name** | إبراهيم سليمان |

**Scenario:** Ibrahim sees a Grafana alert: batch B-2026-05-001 has 8 failed transactions (insufficient balance for 2 employees, wallet not activated for 6). He checks the failed employee list, contacts company HR, and retries after fixes.
