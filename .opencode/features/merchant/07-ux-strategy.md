# Merchant UX Strategy

## Design Principles
1. **Receive-first** — Merchant's primary action is receiving payments, not sending
2. **Voice-powered** — Critical events read aloud (amount received, payment failed) for merchants who don't look at screen
3. **Low-literacy first** — Icons, colors, and voice over text; Arabic-only as default
4. **Offline resilience** — QR scanning and payment links must work with zero internet
5. **Zero training** — Street vendor can start accepting payments in 2 minutes
6. **Trust and transparency** — Every settlement shows exact MDR calculation
7. **Mobile-first, app-primary** — Everything works from phone; web dashboard is secondary

## Information Architecture
```
Merchant App Navigation:
  Home (Dashboard)
    ├── Today's Sales (big number + trend arrow)
    ├── Recent Transactions (scrollable, last 10)
    ├── Quick Actions (QR Code, Payment Link, Add Customer)
    └── Settlement Status (pending/completed, net amount)

  Transactions
    ├── Full history (searchable, filterable)
    ├── Each txn: amount, customer (phone), time, status
    └── Tap → detail (receipt, refund option)

  Products
    ├── QR Codes (list of QR codes per store/table/counter)
    ├── Payment Links (recent links, active, expired)
    └── POS Terminals (paired devices, status)

  Settlements
    ├── Today's settlement preview (estimated)
    ├── Settlement history (daily list)
    └── Each settlement: gross, MDR, net, download PDF

  More
    ├── Profile (business info, verification status)
    ├── Webhook Configuration
    ├── Settings (notification prefs, language)
    ├── Support (FAQ, chat)
    └── Invite Merchant (referral)
```

## Key Screens & Their Goals

### Merchant Home Screen
- **Business Goal**: Show daily sales, trigger quick payment actions, build trust
- **Psychological Goal**: Merchant feels like a real business, professional, growing
- **Trust Signal**: Big number for today's sales, settlement countdown, recent payments
- **Layout**: Hero: Today's sales (large, animated), Quick action buttons (QR, Link), Recent payments list

### QR Code Display Screen
- **Business Goal**: Make it easy to show QR to customers
- **Psychological Goal**: Merchant feels proud to show their business QR
- **Trust Signal**: QR with business name + logo, bright colored frame
- **Layout**: Large QR code centered, business name + logo above, amount entry toggle, brightness boost button

### Payment Link Creation Screen
- **Business Goal**: Generate shareable payment link in under 10 seconds
- **Psychological Goal**: Merchant feels efficient, modern, responsive to customers
- **Trust Signal**: Preview of how link will appear to customer
- **Layout**: Amount input → Description → Optional expiry → Generate → Share sheet

## Transaction States (Merchant UI)
| State | Visual | Action Available |
|-------|--------|------------------|
| Completed | Green checkmark + "تم الاستلام" | View receipt, share |
| Processing | Spinner + "قيد التنفيذ" | Wait |
| Refunded | Blue arrow + "مسترجع" | View details |
| Disputed | Red warning + "نزاع" | Contact support |

## Empty States
| Screen | Empty State | CTA |
|--------|------------|-----|
| Home (no sales) | "لم تستلم أي مدفوعات بعد" | "اعرض QR code لزبونك" (Show QR) |
| Transactions | "لا توجد معاملات" | "شارك رابط الدفع" (Share payment link) |
| Settlements | "لم تتم أي تسوية بعد" | "انتظر أول دفعة" (Wait for first payment) |
| POS Terminals | "لا توجد أجهزة مقترنة" | "إقران جهاز جديد" (Pair new terminal) |
