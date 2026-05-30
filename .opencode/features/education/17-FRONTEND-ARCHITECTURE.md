# 17 — Frontend Architecture

## 17.1 Web Application (React/Next.js)

### Route Structure

| Route | Page | Access |
|---|---|---|
| `/education` | Education home — parent view | Parent (auth) |
| `/education/students/:id` | Student fee detail | Parent |
| `/education/pay/:invoiceId` | Payment flow | Parent |
| `/education/history` | Payment history | Parent |
| `/education/schedule` | Auto-pay schedule | Parent |
| `/education/finance` | Financing/instalment plans | Parent |
| `/school/dashboard` | School analytics overview | School staff |
| `/school/students` | Student list + fee status | School staff |
| `/school/fee-templates` | Fee template management | School staff |
| `/school/invoices` | Invoice management | School staff |
| `/school/communications` | Bulk reminders | School staff |
| `/school/reports` | Reports & exports | School staff |
| `/school/settings` | School profile, staff, bank | School staff |
| `/admin/education` | Platform admin dashboard | Beza admin |
| `/admin/education/schools` | School approval queue | Beza admin |

### Key Components

```
src/
├── features/
│   └── education/
│       ├── components/
│       │   ├── StudentCard.tsx         — Summary card per child
│       │   ├── FeeBreakdown.tsx        — Itemised invoice table
│       │   ├── PaymentMethodSelector.tsx
│       │   ├── ReceiptCard.tsx         — Post-payment receipt
│       │   ├── SchoolDashboardOverview.tsx — KPI cards
│       │   ├── StudentTable.tsx        — Sortable/filterable table
│       │   ├── BulkReminderForm.tsx    — WhatsApp/SMS composer
│       │   ├── FeeTemplateBuilder.tsx  — Line items + discounts
│       │   ├── CollectionChart.tsx     — Trend chart
│       │   └── QRCodeDisplay.tsx       — Enrolment QR
│       ├── pages/
│       │   ├── EducationHome.tsx
│       │   ├── FeeDetail.tsx
│       │   ├── PaymentFlow.tsx
│       │   ├── SchoolDashboard.tsx
│       │   ├── SchoolStudents.tsx
│       │   └── SchoolReports.tsx
│       ├── hooks/
│       │   ├── usePayments.ts
│       │   ├── useStudents.ts
│       │   ├── useDashboard.ts
│       │   └── useFeeTemplates.ts
│       └── api/
│           └── education.ts           — API client functions
├── shared/
│   ├── components/
│   │   ├── DataTable.tsx              — Reusable sortable table
│   │   ├── StatusBadge.tsx            — Paid/Pending/Overdue badge
│   │   └── MoneyDisplay.tsx           — SYP currency formatter
│   └── utils/
│       └── format.ts
```

## 17.2 State Management

- **Server state**: React Query (TanStack Query) — all API data
- **Local form state**: React Hook Form + Zod validation
- **Global state**: Zustand (minimal — auth user, selected school context)

## 17.3 RTL / Arabic Considerations

- Full RTL layout using `dir="rtl"` on the education section
- Arabic-first: all default text in Arabic, English as secondary
- Number formatting: Eastern Arabic numerals (٩٨٧٦٥٤٣٢١) optional, Hindu-Arabic (123) default
- Date format: `dd/mm/yyyy` per Syrian convention
- Currency: "ل.س" suffix for SYP, not "SYP"

## 17.4 Performance Requirements

- Dashboard load: < 2s (cached aggregation)
- Payment flow: < 5s end-to-end (including auth)
- Student list (1000+): virtual scrolling, < 1s render
- Receipt PDF download: < 3s (generated on request)
