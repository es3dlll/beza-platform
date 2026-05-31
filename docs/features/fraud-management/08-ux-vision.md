# UX Vision — Fraud Management

## Design Philosophy

The fraud prevention system should be **invisible to 97% of users** (the false positive target). When a user IS impacted — either their transaction is slowed, flagged, or blocked — the experience must be:

1. **Empathetic** — "We want to keep your money safe, not inconvenience you."
2. **Transparent** — "Here's why this happened and what you can do."
3. **Fast-resolving** — "Your issue will be resolved within [timeframe]."
4. **Low-friction** — "One tap to appeal. Zero friction if it's a mistake."

## User States & Experience

### The 97% — Unflagged Users

These users should NEVER see fraud prevention. Their experience:

- Transaction completes in < 2 seconds (fraud screening adds < 200ms backend)
- No additional verification steps unless high-risk
- No "fraud check" branding or messaging

### The 2.5% — Flagged (False Positive Candidate)

These users see a minor friction point:

```
┌─────────────────────────────────────────────────┐
│  🔒 Beza Security Check                         │
│                                                 │
│  This transaction needs a quick verification    │
│  to protect your account.                       │
│                                                 │
│  [Verify with PIN]   [Verify with OTP]         │
│                                                 │
│  Or tap "This is me" to confirm.               │
│  ┌──────────────────────┐                       │
│  │ ✓ This is me         │                       │
│  └──────────────────────┘                       │
│                                                 │
│  Your money is safe with Beza.                  │
└─────────────────────────────────────────────────┘
```

**Key UX rules:**

- NEVER use alarming language ("fraud", "suspicious", "blocked") for potential false positives
- ALWAYS offer instant verification path
- If user verifies successfully → no record of "suspicion" on user's history
- If user appeals → resolve within 30 minutes

### The 0.3% — Confirmed Fraud or High-Risk

These users receive clear, direct communication:

```
┌─────────────────────────────────────────────────┐
│  ⚠️ Transaction Paused                         │
│                                                 │
│  We paused this transaction because it looks    │
│  unusual for your account. This is to protect   │
│  your money.                                    │
│                                                 │
│  Transaction: 50,000 SYP to Merchant XYZ        │
│  Reason: This amount is higher than your usual  │
│           transactions.                         │
│                                                 │
│  What would you like to do?                     │
│                                                 │
│  ┌──────────────────┐  ┌──────────────────┐    │
│  │ ✓ It's me        │  │ 🚫 Stop this     │    │
│  └──────────────────┘  └──────────────────┘    │
│                                                 │
│  If this wasn't you, we'll freeze your account  │
│  and investigate immediately.                   │
│                                                 │
│  Need help? Contact support:  [Chat] [Call]    │
└─────────────────────────────────────────────────┘
```

### The 0.01% — Account Frozen / Under Investigation

```
┌─────────────────────────────────────────────────┐
│  🔒 Account Temporarily Frozen                  │
│                                                 │
│  Your account has been temporarily frozen       │
│  because we detected unusual activity.          │
│                                                 │
│  What's happening:                              │
│  • A transaction was made from a new device     │
│  • We want to confirm it's really you           │
│                                                 │
│  Case ID: FR-2025-001234                         │
│  Status: Under Investigation                     │
│  Expected resolution: Within 4 hours            │
│                                                 │
│  ┌──────────────────┐  ┌──────────────────┐    │
│  │ Contact Support  │  │ Appeal Decision  │    │
│  └──────────────────┘  └──────────────────┘    │
│                                                 │
│  What to do:                                     │
│  1. Contact support or visit nearest agent      │
│  2. Provide ID verification                      │
│  3. Account restored if confirmed as you        │
└─────────────────────────────────────────────────┘
```

## Tone & Voice — Fraud Communication

| Scenario                   | Tone                              | Example (Arabic)                                      |
| -------------------------- | --------------------------------- | ----------------------------------------------------- |
| False positive candidate   | Calm, reassuring, action-oriented | "لتأكيد هويتك، يرجى إدخال الرمز السري"                |
| High-risk transaction      | Alert but helpful                 | "نحن نحمي حسابك من أي عملية غير مصرح بها"             |
| Confirmed fraud            | Direct, supportive                | "تم تجميد حسابك مؤقتاً لحمايتك. سنتواصل معك قريباً"   |
| Investigation update       | Informative, transparent          | "جاري التحقيق في حالتك. التحديث المتوقع خلال 4 ساعات" |
| Case resolved (user safe)  | Celebratory                       | "تم حل المشكلة. حسابك آمن الآن"                       |
| Case resolved (fraud loss) | Supportive, next steps            | "نأسف لهذه التجربة. إليك الخطوات التالية"             |

## Cultural Considerations (Syria-Specific)

### Arabic Language UX

| English          | Arabic (Syrian dialect preference) |
| ---------------- | ---------------------------------- |
| Transaction      | عملية                              |
| Freeze           | تجميد                              |
| Investigation    | تحقيق                              |
| Fraud            | احتيال                             |
| Appeal           | استئناف / طلب مراجعة               |
| Security Check   | التحقق الأمني                      |
| Unusual Activity | نشاط غير معتاد                     |

**Note:** Use Fus-ha (Modern Standard Arabic) for formal communications (cases, investigations) but Syrian dialect for notifications and in-app messaging to feel approachable.

### Color Psychology in Syria

| Color  | Meaning              | Use                                   |
| ------ | -------------------- | ------------------------------------- |
| Green  | Safe, trusted        | Normal transactions                   |
| Yellow | Caution, delay       | Flagged transactions (soft block)     |
| Red    | Danger, stop         | Blocked transactions, confirmed fraud |
| Blue   | Information, support | Investigation updates                 |
| Gold   | Premium, secure      | Fraud insurance upsell                |

### Trust Signals

Syrian users need to see trust signals prominently:

- Beza logo/branding on every fraud screen
- CBS license number visible
- "مدعوم من المصرف المركزي السوري" (Supported by the Central Bank of Syria)
- Contact information always visible
- Clear "next step" guidance

## Mobile vs Agent vs Web

| Channel                  | Fraud UX Approach                                                                                        |
| ------------------------ | -------------------------------------------------------------------------------------------------------- |
| Mobile App (Android/iOS) | Full interactive experience, push notifications, in-app verification                                     |
| USSD (\*123#)            | SMS-based step verification, limited to PIN/OTP check                                                    |
| Agent POS                | Agent-facing: "Transaction flagged. Ask customer for ID." Customer-facing: receipt with SMS notification |
| Web Portal               | Full dashboard for operations; simplified flow for end users                                             |
| SMS                      | Notification-only: "Your transaction [amount] SYP is paused. Reply YES to confirm."                      |

## Accessibility

(Detailed in 14-accessibility.md — summary here)

- Risk indicators: color + icon + text (never color alone)
- Screen reader support for all fraud screens
- High contrast mode for case management dashboard
- Font size adjustable for operations team

## Key UX Flows

### Flow 1: Transaction Screening (User-unaware)

```
User initiates → Send to backend → Fraud engine scores → [low risk → approve → done]
                                                         [med risk → slow + verify → done]
                                                         [high risk → block + notify → show screen]
```

### Flow 2: Fraud Appeal

```
User sees flag → "This is me" → [automatic review → passes → transaction completes]
                                  [still suspicious → escalated to team → resolve < 30min]
```

### Flow 3: Account Frozen

```
User sees freeze → Contact support → Verify identity → [pass → account restored]
                                                         [fail → remains frozen → investigation]
```

## Success Metrics for UX

| UX Metric                           | Target   | Measurement                               |
| ----------------------------------- | -------- | ----------------------------------------- |
| User appeal resolution time         | < 30 min | System tracked                            |
| False positive user satisfaction    | > 4.5/5  | Post-interaction survey                   |
| Fraud victim satisfaction           | > 3.5/5  | Post-resolution survey                    |
| User understanding of freeze reason | > 80%    | "Did you understand why?" survey          |
| Appeal abandonment rate             | < 5%     | Users who start appeal but don't complete |
| Support calls per fraud case        | < 1.5    | Reduce by making in-app flow clear        |
