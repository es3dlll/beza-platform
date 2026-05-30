# Wallet UX Strategy

## Design Principles
1. **Cash-like speed** — Every action must feel as fast as handing cash
2. **No training required** — First-time user can send money in < 60 seconds
3. **Confidence at every step** — Clear amount display, fee transparency, confirmation
4. **Multi-channel consistency** — Same experience across app, USSD, and agent
5. **Offline resilience** — All critical flows work partially or fully offline
6. **Low-literacy friendly** — Icons, colors, voice guidance over text
7. **Arabic-first** — RTL is default, English secondary

## Information Architecture
```
Wallet App Navigation:
  Home (Dashboard)
    ├── Balance card (SYP + USD)
    ├── Quick Actions (Send, Pay, Top-up, Agent)
    ├── Recent Transactions (scrollable)
    └── Savings Goals (progress bars)

  Send
    ├── Contact selection (phonebook + recent)
    ├── Amount entry
    ├── Confirmation (fee breakdown)
    └── Success/Failure

  Request
    ├── Amount entry
    ├── Contact selection
    └── Sent confirmation

  Pay (Bills)
    ├── Category grid (Electricity, Water, Telecom, Government)
    ├── Customer ID entry
    ├── Bill detail
    └── Confirmation

  More
    ├── Profile
    ├── Cards
    ├── Savings
    ├── Transaction history
    ├── Settings
    └── Support
```

## Key Screens & Their Goals

### Home Screen
- **Business Goal**: Show balance, enable quick actions, drive engagement
- **Psychological Goal**: User feels wealthy, in control, informed
- **Trust Signal**: Large balance display, real-time updates, recent txn list
- **Layout**: Balance card (hero) + quick actions grid + recent txns + savings progress

### Send Money Screen
- **Business Goal**: Complete maximum transfers with minimum friction
- **Psychological Goal**: User feels generous, connected, efficient
- **Trust Signal**: Fee shown BEFORE confirmation, recipient name + photo
- **Layout**: Contact search → amount → fee breakdown → confirmation

### Transaction History
- **Business Goal**: Reduce support tickets, increase trust through transparency
- **Psychological Goal**: User feels organized, in control of finances
- **Trust Signal**: Every txn has status badge, receipt CTA
- **Layout**: Filter tabs (All, Sent, Received, Bills) → list with icons → detail

## Transaction States (UI Display)
| State | Visual | Action Available |
|-------|--------|------------------|
| Processing | Spinner + "جاري التنفيذ" | Wait |
| Completed | Green checkmark + "تم" | View receipt |
| Failed | Red X + "فشل" + reason | Retry |
| Pending | Amber clock + "معلق" | None |
| Refunded | Blue arrow + "مسترجع" | View details |
| Disputed | Red warning + "نزاع" | Contact support |

## Empty States
| Screen | Empty State | CTA |
|--------|------------|-----|
| Home | No balance yet | "قم بشحن محفظتك" (Fund your wallet) |
| Transactions | No transactions | "أرسل أو اطلب أموالاً" (Send or request money) |
| Contacts | No contacts | "ادعُ أصدقائك" (Invite friends) |
| Savings | No goals | "ابدأ هدفاً" (Create a goal) |
| Cards | No cards | "اطلب بطاقة" (Request a card) |
