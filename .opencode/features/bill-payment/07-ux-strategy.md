# Bill Payment UX Strategy

## Design Principles
1. **Zero learning curve** — Any Syrian who has ever paid a bill should complete first payment in < 90 seconds
2. **Confidence through transparency** — Show bill breakdown, fees, due date, and late penalties before confirmation
3. **Proactive not reactive** — Remind users BEFORE due date, not after
4. **Multi-channel** — Pay via app, USSD (*123# → 3 → Bill Pay), or through any Beza Agent
5. **Biller-aware validation** — Customer ID format validation per biller (24-digit PEED, 10-digit water, 10-digit mobile)
6. **Receipt certainty** — Every payment generates a receipt with both Beza and biller reference numbers
7. **Arabic-first** — RTL default, all biller names and categories in Arabic

## Information Architecture
```
Bill Payment Flow:
  Pay Bills (Home)
    ├── Category Grid
    │   ├── 🔌 كهرباء (Electricity)
    │   │   └── PEED (الشركة العامة للكهرباء)
    │   │   └── Aleppo Electricity (future)
    │   ├── 💧 مياه (Water)
    │   │   └── Damascus Water Authority
    │   │   └── Homs Water Authority (future)
    │   ├── 📞 اتصالات (Telecom)
    │   │   ├── Syriatel (سيريتل)
    │   │   ├── MTN (إم تي إن)
    │   │   └── Syria Telecom (الاتصالات)
    │   ├── 🌐 إنترنت (Internet)
    │   │   ├── Aya (آية)
    │   │   └── Saman (سامان)
    │   ├── 🏛 حكومة (Government)
    │   │   └── Civil Affairs Fees
    │   │   └── Passport Fees
    │   │   └── Justice Fees
    │   └── 🎓 تعليم (Education)
    │       ├── Damascus University
    │       └── Al-Sham University
    │
    ├── Customer ID Entry
    │   ├── Biller-specific keyboard hint
    │   └── Format validation + helper text
    │
    ├── Bill Detail (Fetched)
    │   ├── Bill breakdown
    │   ├── Late fees if applicable
    │   └── Payment options
    │
    ├── Confirmation (Bottom Sheet)
    │   ├── Amount + fee summary
    │   ├── PIN input
    │   └── Biometric option
    │
    └── Result
        ├── Success (Green → Receipt)
        └── Failure (Red → Reason + Retry)

  Bills Tab (Bottom Nav)
    ├── My Bills (Fetched + Unpaid)
    ├── Scheduled / Reminders
    └── History (All payments, filterable)
```

## Key Screens & Their Goals

### Bill Category Grid
- **Business Goal**: Help user find their biller quickly across 15+ options
- **Psychological Goal**: "Wow — they have ALL my billers"
- **Trust Signal**: Biller logos (when available) + clear Arabic names
- **Layout**: 3-column category grid → expand to biller list per category

### Customer ID Entry
- **Business Goal**: Capture the correct ID on first attempt (reduce support tickets)
- **Psychological Goal**: "The app knows exactly what format my biller uses"
- **Trust Signal**: Biller logo + ID format example + auto-formatting
- **Behavior**: Numeric keyboard, auto-grouping (XXXX-XXXX-XXXX), paste support, scan ID from OCR (Phase 2)

### Bill Detail Screen
- **Business Goal**: User confirms payment with full understanding of charges
- **Psychological Goal**: "I see exactly what I'm paying for — no surprises"
- **Trust Signal**: Biller reference number, due date, breakdown of charges
- **Layout**: Hero amount → breakdown table → late fee warning → action buttons

### Scheduled Bills Screen
- **Business Goal**: Drive recurring usage and auto-pay adoption
- **Psychological Goal**: "Set it and forget it — my bills are handled"
- **Trust Signal**: Clear next due date, status indicators for upcoming payments
- **Layout**: List of scheduled bills with status badges + next payment info

## Transaction States (UI Display)
| State | Visual | Action Available |
|-------|--------|------------------|
| Fetching | Spinner + "جارٍ الاستعلام..." | Cancel |
| Fetched | Green checkmark + "تم الاستعلام" | Pay / Save |
| Fetch Failed | Red X + "فشل الاستعلام" + reason | Retry |
| Processing | Spinner + "جارٍ الدفع..." | Wait |
| Paid | Green checkmark + "تم الدفع" | View receipt |
| Failed | Red X + "فشل الدفع" + reason | Retry / Support |
| Refunded | Blue arrow + "مسترجع" | View details |
| Queued | Amber clock + "قيد المعالجة" | None (auto-process) |
| Partial | Amber half-check + "مدفوع جزئياً" | Pay remainder |

## Empty States
| Screen | Empty State | CTA |
|--------|------------|-----|
| Bill Categories | No billers loaded | "تحميل الفئات" (Load categories) |
| Bill Detail | No bill found for this ID | "تحقق من الرقم وحاول مجدداً" |
| History | No payments yet | "ادفع أول فاتورة" |
| Scheduled | No reminders set | "حدد تذكيراً لفاتورة" |
| Search Results | No matching transactions | "حاول بكلمة بحث مختلفة" |
