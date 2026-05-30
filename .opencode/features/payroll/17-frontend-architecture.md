# 17 — Frontend Architecture (Company Dashboard)

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | React 18 + TypeScript |
| State | React Query (server) + Zustand (client) |
| Styling | Tailwind CSS + custom design system |
| Forms | React Hook Form + Zod validation |
| Language | Arabic (RTL) — first-class; English fallback |

## Key Pages

| Route | Page | Description |
|-------|------|-------------|
| `/payroll/dashboard` | Dashboard | Balance, quick stats, recent batches |
| `/payroll/batches` | Batch List | Filterable, paginated list of payroll runs |
| `/payroll/batches/new` | New Batch | Upload CSV / enter employees, review, confirm |
| `/payroll/batches/:id` | Batch Detail | Per-employee status, retry buttons, download payslips |
| `/payroll/employees` | Employee Roster | Add, edit, terminate employees |
| `/payroll/settings` | Company Settings | Profile, settlement config, API keys, webhook |

## Component Architecture

```
src/
  features/
    payroll/
      components/
        BatchStatusBadge.tsx      # Coloured status pill
        BatchProgressBar.tsx      # Real-time processing progress
        EmployeeTable.tsx         # Sortable employee list with status
        CSVUploader.tsx           # Drag-and-drop CSV upload with validation preview
        PayslipDownloadButton.tsx # Single or bulk download
        RetryButton.tsx           # Retry failed transaction(s)
        BalanceCard.tsx           # Company balance display
        PinConfirmModal.tsx       # 2FA PIN confirmation before batch execution
      pages/
        DashboardPage.tsx
        BatchListPage.tsx
        NewBatchPage.tsx
        BatchDetailPage.tsx
        EmployeeRosterPage.tsx
        CompanySettingsPage.tsx
      hooks/
        useBatches.ts
        useBatchDetail.ts
        useEmployees.ts
        useCompanyBalance.ts
      api/
        payrollApi.ts             # Axios client for payroll endpoints
```

## RTL / Arabic Considerations

- All layouts use `dir="rtl"` by default
- Tailwind: custom RTL spacing tokens (`mr-*` → `ms-*`)
- Date formatting: `ar-SY` locale
- Currency: `SYP ١٬٢٠٠٬٠٠٠` (Arabic-Indic digits optional via user preference)
- Toast notifications in Arabic

## Key UI Flows

### New Batch Flow

1. `/payroll/batches/new` → Select upload method: CSV or manual entry
2. CSV uploader validates on client side (columns, amounts, employee existence)
3. Preview table shows: employee name, amount, expected fee, validation status
4. "Confirm Batch" button → PIN modal → `POST /payroll/batch`
5. Redirect to batch detail page with real-time progress via polling

### Retry Flow

1. Batch detail page shows failed rows in red
2. HR clicks "Retry" (per row or "Retry All Failed")
3. Confirmation modal → `POST /payroll/batch/{id}/retry`
4. Row updates optimistically, then syncs with server
