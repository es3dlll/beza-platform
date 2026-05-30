# 02 — Scope

## In Scope

| Feature | Priority | Notes |
|---------|----------|-------|
| Company onboarding (KYC, license, payroll account) | P0 | Mandatory to start |
| CSV salary sheet upload | P0 | Template validation |
| Bulk wallet credit (debit company → credit employees) | P0 | Core transaction |
| Instant employee notification (push + SMS) | P0 | Arabic content |
| PDF payslip generation | P0 | Per employee per batch |
| Company dashboard (balance, batch history, employee list) | P1 | Web + mobile |
| Employee payroll history | P1 | In Beza wallet |
| Retry failed transfers (3 attempts) | P1 | Exponential backoff |
| Partial batch success / failure reporting | P1 | Detailed per-employee |
| Settlement — pre-funded account | P1 | Company deposits before payroll |
| Settlement — T+1 settlement | P2 | Credit line for trusted companies |
| API for direct integration (ERP systems) | P2 | REST + webhook callbacks |
| Multi-currency (SYP, USD) | P2 | SYP-first, USD for NGOs |
| Salary advances | P3 | Employee requests early draw |
| Recurring / schedule-based payroll | P3 | Auto-run on fixed dates |
| Government tax integration | P3 | MoF API (future) |

## Out of Scope (v1.0)

- International wire transfers
- Physical payroll cards / ATM cards
- Employee loans or lending products
- Full HRIS / attendance tracking
- Social Security contributions processing (Phase 2)
- Income tax withholding & filing (Phase 2)
- Cheque printing
- Payroll for > 5,000 employee companies (Phase 3)
