# Cards UX Strategy

## Design Principles
1. **30-second card** — From tap to card ready in under 30 seconds
2. **See your card** — Full card visual with PAN/CVV/expiry displayed like a physical card
3. **Confidence at POS** — Clear card art, network logo, last four digits for identification
4. **One-tap freeze** — Critical action (freeze) accessible in < 2 taps from anywhere
5. **Multi-card management** — Swipeable card carousel for users with multiple cards
6. **Arabic-first** — RTL default, all card text in Arabic including merchant names
7. **Privacy by default** — Card numbers hidden until authenticating, never in screenshots

## Information Architecture
```
Cards Tab Navigation:
  Cards Home (Card Carousel)
    ├── Card 1 (Active) [front: PAN hidden, art, network, last-4]
    │   ├── Freeze/Unfreeze toggle
    │   ├── Limits (Online, POS, ATM, Intl)
    │   ├── PIN Management
    │   ├── Transactions
    │   ├── Add to Wallet (Apple Pay / Google Pay)
    │   ├── Card Details (show PAN, CVV, expiry with auth)
    │   └── Replace / Report Lost
    │
    ├── Card 2 (Frozen) [greyed out]
    │   └── Unfreeze
    │
    ├── [+] Create New Card
    │
    ├── One-Time Card (quick action)
    │
    └── Settings
        ├── Default spending limits
        ├── Notification preferences
        ├── Card order history
        └── Saved merchant tokens
```

## Key Screens & Their Goals

### Card Carousel Screen
- **Business Goal**: Show all cards, enable quick freeze, drive activation
- **Psychological Goal**: User feels in full control of their cards
- **Trust Signal**: Instant visual status (active/frozen/lost), card art with network logo
- **Layout**: Horizontal card carousel with page indicator, action buttons below

### Card Detail Screen
- **Business Goal**: Enable full card management from one screen
- **Psychological Goal**: User feels secure with granular controls
- **Trust Signal**: PAN shown only after biometric/PIN verification
- **Layout**: Card preview → quick stats (spent today, remaining) → action grid → recent txns

### Create Card Screen
- **Business Goal**: Convert wallet users to card users with minimal friction
- **Psychological Goal**: Excitement of "unlocking" a new payment method
- **Trust Signal**: Clear fee display before creation, card art preview
- **Layout**: Card type selector → currency → limits → confirmation → card reveal

### One-Time Card Screen
- **Business Goal**: Generate single-use cards for high-risk transactions
- **Psychological Goal**: User feels protected when shopping at unknown merchants
- **Trust Signal**: Auto-destroy timer, "safe shopping" badge
- **Layout**: Amount input → generate → show card → "لقد تم تدمير هذه البطاقة تلقائياً"

## Transaction States (UI Display)
| State | Visual | Action Available |
|-------|--------|------------------|
| Authorized | Blue clock + "مصرح به" | None (pending settlement) |
| Settled | Green check + "تمت التسوية" | View receipt |
| Declined | Red X + "مرفوض" + reason | Retry |
| Refunded | Blue arrow + "مسترجع" | View details |
| Pending | Amber clock + "قيد المعالجة" | None |
| Reversed | Grey arrow + "ملغي" | View details |

## Empty States
| Screen | Empty State | CTA |
|--------|------------|-----|
| Cards Home | No cards yet + illustration | "أنشئ بطاقتك الأولى" (Create your first card) |
| Card Transactions | No transactions | "استخدم بطاقتك للشراء" (Use your card) |
| One-Time Cards | No one-time cards used | "جرب الدفع الآمن" (Try secure payment) |
| Card Limits | Default limits active | "خصص حدودك" (Customize limits) |
