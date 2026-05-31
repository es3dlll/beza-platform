# FX Engine UX Strategy

## Design Principles
1. **Radical transparency** — Show the rate, its sources, and the spread. No hidden markup.
2. **Speed of cash** — Rate display updates in real-time. Conversion feels instant.
3. **Lock confidence** — The rate lock timer must build trust, not anxiety.
4. **Low cognitive load** — Don't overwhelm with multiple rates; show one optimized Beza rate, expand for details.
5. **Arabic-first** — RTL layout, Arabic number formatting (١٢٬٥٠٠), SYP symbol ل.س.
6. **Offline awareness** — Clearly indicate when rates are cached/stale vs live.
7. **Educational** — Help users understand spread, bid/ask, and why rates differ.

## Information Architecture
```
Exchange (Feature Root)
├── Rate Cards Dashboard
│   ├── SYP/USD Card (expanded: source breakdown, 24h chart)
│   ├── SYP/EUR Card
│   └── USD/EUR Card
│
├── Convert Flow
│   ├── Source currency selection (wallet picker)
│   ├── Target currency selection
│   ├── Amount entry
│   ├── Rate preview (live updating)
│   ├── Lock Rate → Confirm → Execute
│   └── Success/Failure
│
├── Conversion History
│   ├── List (paginated)
│   └── Detail (receipt)
│
└── Rate Info (Educational)
    ├── What affects SYP rates?
    ├── Why Beza rate differs from CBS
    └── Spread explained
```

## Key Screens & Their Goals

### Exchange Home (Rate Dashboard)
- **Business Goal**: Show rates prominently, drive conversion volume
- **Psychological Goal**: User feels informed, confident in fairness
- **Trust Signal**: Show source breakdown, last updated timestamp, rate comparison
- **Layout**: Rate card grid → tap to expand → source details + chart

### Convert Screen
- **Business Goal**: Complete maximum conversions with minimum friction
- **Psychological Goal**: User feels they got a fair deal, had control
- **Trust Signal**: Rate lock timer, fee breakdown BEFORE confirmation
- **Layout**: Wallet picker → amount → rate preview with lock → confirm

### Rate Lock Timer
- **State visualization**: Circular countdown (green > 15s, amber < 10s, red < 5s)
- **Lock animation**: Subtle glow effect while rate is held
- **Expiry**: Smooth transition to "Rate expired — tap to get new rate"

## Transaction States (UI Display)
| State | Visual | Action Available |
|-------|--------|------------------|
| Fetching rate | Pulsing skeleton | Wait |
| Rate locked | Green glow + countdown | Confirm conversion |
| Converting | Spinner + "جاري التحويل" | Wait |
| Completed | Green checkmark + "تم" | View receipt |
| Failed | Red X + "فشل" + reason | Retry |
| Rate expired | Amber + "انتهت صلاحية السعر" | Get new rate |
| Stale rate | Amber indicator | Refresh |

## Empty States
| Screen | Empty State | CTA |
|--------|------------|-----|
| Exchange Home | "No rates available" with retry | "إعادة المحاولة" (Retry) |
| Conversion History | "No conversions yet" | "تحويل الآن" (Convert now) |
| Rate Sources | "No providers configured" (admin) | "إضافة مزود" (Add provider) |
