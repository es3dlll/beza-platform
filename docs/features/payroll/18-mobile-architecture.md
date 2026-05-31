# 18 — Mobile Architecture (Employee Wallet)

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | React Native (Expo) |
| State | React Query + Zustand |
| Navigation | React Navigation (bottom tabs + stack) |
| Push | Firebase Cloud Messaging |

## Key Screens

| Screen | Route | Description |
|--------|-------|-------------|
| Wallet Home | `/wallet` | Balance, recent transactions, salary banner |
| Salary Detail | `/salary/:batch_id` | Full salary breakdown + payslip download |
| Salary History | `/salary/history` | Chronological list of all payments |
| Payslip View | `/payslip/:tx_id` | In-app PDF viewer for payslip |

## Employee Payroll Flow

```
Push notification arrives:
  "تم إيداع راتبك: 1,200,000 ل.س من شركة الشام للصناعات الحديدية"

User taps → opens app → Salary Detail screen:
  • Company name + logo
  • Amount: 1,200,000 SYP
  • Date: 29 مايو 2026
  • Batch reference: B-2026-05-001
  • Button: "عرض كشف الراتب" (View Payslip)
  • Button: "مشاركة" (Share via WhatsApp)
```

## API Integration

| Endpoint | Mobile Usage |
|----------|-------------|
| `GET /payroll/employee/{user_id}/history` | Salary history |
| `GET /payroll/v1/payslip/{tx_id}/download?token=...` | Download payslip PDF |
| `POST /auth/v1/wallet/verify` | Initial wallet activation (for new employees) |

## Offline Considerations

- Salary history cached locally (React Query persistent)
- Payslip PDFs can be saved to device storage
- Notifications delivered via FCM regardless of app state
