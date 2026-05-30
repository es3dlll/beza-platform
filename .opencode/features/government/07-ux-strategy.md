# Government Collections UX Strategy

## Core Principles
1. **Zero friction** — Paying government fees should take <30 seconds. No registration for one-time payments.
2. **Trust first** — Official receipts, real-time ministry confirmation, settlement tracking.
3. **Arabic-first** — Full Arabic interface, Arabic-Indic numerals option, Syrian dialect copy.
4. **Proactive** — Know when fees are due before the user asks. Reminders, calendar sync, deadline alerts.
5. **Inclusive** — Agent-assisted mode, USSD for feature phones, large text option for elderly.

## Interaction Design

### Service Hub Pattern
```
All government services live in a single hub with:
- Search: type any fee name (ضريبة, جواز, جامعة, مركبة, مخالفة, بلدية)
- Categories: visual grid with icons (tax, vehicle, passport, university, civil, court, municipal)
- Favourites: user's most-used services at top
- Recent: last 3 paid services for quick re-payment
- Smart prompts: "لديك ضريبة عقارية مستحقة" based on saved data
```

### Payment Flow Principles
| Principle | Implementation |
|-----------|---------------|
| Show before ask | Always display amount due before requesting payment |
| Breakdown clarity | Show base fee + any penalties/fees before total |
| Deadline prominence | Countdown timer for time-sensitive payments |
| Receipt as destination | After payment, default action is receipt (not "done") |
| Share-friendly | One-tap share via WhatsApp, SMS, email |
| Offline resilience | Queue payment if temporarily offline, execute when online |

### Notification Strategy
| Trigger | Channel | Message |
|---------|---------|---------|
| Tax deadline in 30 days | Push + SMS | "باقي 30 يوم على آخر موعد لدفع ضريبة الدخل" |
| Tax deadline in 7 days | Push | "باقي أسبوع — رصيدك الحالي يكفي للسداد" |
| Tax deadline today | Push + SMS | "آخر يوم لدفع الضريبة. ادفع الآن لتجنب الغرامة" |
| Vehicle registration expiry | Push | "تجديد ترخيص مركبتك ينتهي بعد 14 يوم" |
| Tuition fee deadline | Push + SMS | "آخر موعد لتسديد رسوم الجامعة بعد 3 أيام" |
| Traffic fine issued | Push | "مخالفة مرورية جديدة — رقم اللوحة ١٢٣٤٥" |
| Payment confirmed | Push | "✅ تم دفع ٢٦٢,٥٠٠ ل.س ضريبة الدخل" |
| Payment to ministry settled | Push | "تمت تسوية دفعتك مع وزارة المالية بنجاح" |
| Reconciliation mismatch | SMS + Email | "يرجى مراجعة إيصال الدفع — تباين في التسوية" |

## Accessibility
- **Large type mode**: 150% default text size for elderly users
- **Agent assist**: "واسطة" mode — remote agent takes control with user permission
- **Colour-blind friendly**: Payment status uses icons (✅, ⏳, ❌) not just colours
- **Screen reader**: Full TalkBack / VoiceOver support with Arabic
- **Simple language**: Avoid government bureaucratic terms — use common Syrian Arabic
