# 09 — Priorities & Roadmap

## 9.1 Delivery Phases

### Phase 1 — Core Payments (Weeks 1-8)
**Target**: 20 schools, 8,000 students, 3 B SYP TPV
- FP-01 School fee payment (wallet)
- FP-02 Fee breakdown view
- FP-03 Payment history
- FP-04 Multi-child dashboard
- FP-08 Digital receipt with QR
- FS-01 Fee management dashboard
- FS-02 Student enrolment list
- FS-03 Fee template builder
- FS-04 Auto-invoicing
- FS-08 CSV/Excel export
- FS-14 Deposit/settlement report
- FO-01 Merchant onboarding
- FO-03 Reconciliation engine (Phase-1 scope)
- FO-05 Notification engine (baseline)
- FO-07 Audit log

### Phase 2 — School Power Tools (Weeks 9-16)
**Target**: 45 schools, 25,000 students, 20 B SYP TPV
- FP-05 Push/SMS reminders (for parents)
- FP-06 Auto-pay scheduling
- FS-05 Bulk reminder (WhatsApp/SMS)
- FS-06 Receipt bulk download
- FS-09 Multi-faculty/branch management
- FS-12 API for ERP integration
- FS-13 Enrolment QR generation
- FO-02 Multi-currency settlement (diaspora)
- FO-06 Reporting & analytics

### Phase 3 — Financing & Advanced (Weeks 17-24)
**Target**: 100 schools, 50,000+ students, 50 B SYP TPV
- FP-07 Instalment plan sign-up
- FP-09 Diaspora payment (FX)
- FP-10 Dispute a fee
- FP-11 Download tax certificate
- FP-12 Share payment link
- FP-13 Attendance-linked fees
- FS-07 Partial payment recording
- FS-10 Staff management
- FS-11 Fee change notification
- FO-04 Financing engine
- FO-08 School directory

## 9.2 Milestones

| Milestone | Date | KPI |
|---|---|---|
| M0 — Kickoff | Week 0 | Team assembled, specs signed off |
| M1 — Parent payment MVP | Week 6 | 5 schools live, 500 payments processed |
| M2 — School dashboard beta | Week 8 | 20 schools actively using dashboard |
| M3 — Bulk notifications | Week 12 | 10,000+ reminders sent |
| M4 — Financing launch | Week 18 | 100+ instalment plans issued |
| M5 — Diaspora payments | Week 20 | 50+ international payments |
| M6 — Scale | Week 24 | 100 schools, 50K students |

## 9.3 Feature Dependency Map

```
Merchant Onboarding (FO-01)
       │
       ▼
Fee Template Builder (FS-03) ──→ Auto-Invoicing (FS-04)
                                           │
                              ┌────────────┴────────────┐
                              ▼                         ▼
                 Fee Payment (FP-01) ←───────    Enrolment QR (FS-13)
                              │
                              ▼
              Reconciliation Engine (FO-03)
                              │
                    ┌─────────┴────────┐
                    ▼                   ▼
            School Dashboard      Parent History
            (FS-01)               (FP-03)
                    │
                    ▼
           Bulk Reminders (FS-05) ──→ Auto-Pay (FP-06)
                    │
                    ▼
           Financing Engine (FO-04) ──→ Instalments (FP-07)
```
