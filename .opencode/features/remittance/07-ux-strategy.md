# Remittance UX Strategy

## Design Principles
1. **Trust through transparency** — Every fee, FX rate, and conversion shown clearly before confirmation
2. **Speed of cash, convenience of digital** — Complete a remittance in < 60 seconds
3. **Zero surprise fees** — All costs disclosed upfront, no hidden charges
4. **Arabic-first, diaspora-aware** — RTL Arabic default, with English/German/Swedish secondary
5. **Recipient dignity** — Recipient experience is as important as sender
6. **Offline resilience** — Critical flows work on USSD for unregistered recipients
7. **Compliance without friction** — Gather KYC data progressively, not all at once

## Information Architecture
```
Remittance App Navigation:

  Send (Primary)
    ├── Recipient (saved beneficiaries + phonebook)
    ├── Amount + Currency selection
    ├── FX preview (if cross-currency)
    ├── Fee breakdown
    ├── Delivery option (wallet / agent pickup / bank)
    ├── Confirmation with PIN/biometric
    └── Success screen with receipt

  Request
    ├── Amount + Currency
    ├── Contact selection
    └── Sent confirmation

  Beneficiaries
    ├── Saved list (name, phone, relationship)
    ├── Add beneficiary
    └── Transfer history with that beneficiary

  Recurring
    ├── Active recurring transfers
    ├── Create new recurring
    ├── Execution history
    └── Pause / Cancel

  History
    ├── All transfers
    ├── Filters (sent, received, recurring, pending)
    ├── Search
    └── Export

  Corridors (Admin)
    ├── Active corridors (countries)
    ├── Limits per corridor
    ├── FX rates per corridor
    └── Compliance rules per corridor
```

## Key Screens & Their Goals

### Send Money Screen
- **Business Goal**: Complete maximum transfers with minimum drop-off
- **Psychological Goal**: Sender feels generous, connected, in control
- **Trust Signal**: Live FX rate with mid-market comparison, fee broken down
- **Layout**: Beneficiary → Amount → FX preview → Fee → Confirm

### Beneficiary List
- **Business Goal**: Encourage repeat sending through saved beneficiaries
- **Psychological Goal**: Sender feels organized, connected to family
- **Trust Signal**: Last sent date, total sent amount, relationship label
- **Layout**: Horizontal scroll of recent, vertical list of saved

### Recurring Transfer Screen
- **Business Goal**: Drive stickiness through automated recurring flows
- **Psychological Goal**: Sender feels responsible, reliable
- **Trust Signal**: Clear execution schedule, total sent this year
- **Layout**: Frequency picker → Amount → Duration → FX preference

### Transfer Detail Screen
- **Business Goal**: Reduce support tickets, provide transparency
- **Psychological Goal**: Sender feels confident money was delivered
- **Trust Signal**: Timeline view (Initiated → FX Locked → Completed → Received)
- **Layout**: Status timeline, amount, fee, FX rate, receipt download

## Transaction States (UI Display)
| State | Visual | Action Available |
|-------|--------|------------------|
| Processing | Spinner + "جاري التحويل" | Cancel (if within 30 min) |
| FX Locked | Clock + "تم تثبيت سعر الصرف" | Wait |
| Completed | Green checkmark + "تم الاستلام" | View receipt, Repeat |
| Failed | Red X + "فشل" + reason | Retry |
| Pending (Recurring) | Calendar + "مجدول في 01-07-2026" | Edit, Cancel |
| Cancelled | Grey X + "ملغي" | None |
| Disputed | Red warning + "نزاع" | Contact support |

## Empty States
| Screen | Empty State | CTA |
|--------|------------|-----|
| Beneficiaries | "لا يوجد مستفيدين" | "أضف مستفيداً" (Add beneficiary) |
| Recurring | "لا يوجد تحويلات دورية" | "إنشاء تحويل دوري" (Create recurring) |
| History | "لا يوجد تحويلات بعد" | "أرسل أول تحويل" (Send first transfer) |
| Corridors (Admin) | "لا يوجد ممرات تحويل" | "أضف ممراً جديداً" (Add new corridor) |
