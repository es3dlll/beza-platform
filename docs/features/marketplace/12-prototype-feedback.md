# Prototype Feedback

## Phase 1 — Wireframe Prototype (Internal)

### Participants
- 5 Beza product team members
- 2 UI/UX designers
- 1 Technical lead

### Key Findings

| Issue | Severity | Resolution |
|---|---|---|
| Top-up amount buttons too small on 360px screens | High | Increased min-width to 72px; wrapping grid for smaller screens |
| Cart badge not updating immediately after add | Medium | Implemented optimistic UI update + server sync |
| "Send gift card" flow too many steps (7 → 4) | High | Merged recipient + delivery method into one screen |
| Vendor rating not prominent enough on product card | Medium | Moved to top-right of card with gold star icon |
| Insufficient balance error shown only at confirmation | High | Added real-time balance check on amount selection |
| No clear way to re-order past purchases | Low | Added "Reorder" button on order detail screen |

### Changes Implemented
1. Redesigned amount picker with responsive grid
2. Added skeleton loading states for all list views
3. Simplified gift card flow from 5 screens to 3
4. Added wallet balance indicator to header
5. Introduced "Saved favorites" section in top-up

---

## Phase 2 — Clickable Prototype (10 Users)

### User Demographics
- 4 daily smartphone users (Damascus)
- 3 university students (Homs, Aleppo)
- 2 professionals (Latakia)
- 1 small business owner (Idlib)

### Task Success Rates

| Task | Success Rate | Avg Time |
|---|---|---|
| Top up your phone with 5,000 SYP | 100% | 32s |
| Find and buy PUBG 600 UC | 90% | 55s |
| Buy and send a gift card via WhatsApp | 80% | 1m 12s |
| Check order history | 100% | 12s |
| Cancel an order | 70% | 45s |

### User Feedback Quotes

> "أسهل من شراء كرت شحن. فقط رقم والمبلغ وخلص" — It's easier than buying a scratch card. Just the number and amount and done.

> "ودي يكون في خيار إعادة الشحن التلقائي لما الرصيد يقل عن مبلغ معين" — I want an auto top-up option when balance drops below a certain amount.

> "بطاقات الهدايا فكرة حلوة، بس نفسي يكون في متاجر أكثر" — Gift cards are a nice idea, but I wish there were more merchants.

> "حسيت إن في خطوات كثيرة لشراء لعبة. نفسي أضغط مرتين فقط" — Felt like too many steps to buy a game. I want just two clicks.

> "لما يصير خطأ بالشحن لازم يرجعلي المبلغ فوراً" — When a top-up error happens, the amount must be returned to me immediately.

### Priority Improvements
1. **Auto top-up**: Added to roadmap (v1.2)
2. **Express checkout**: One-tap purchase for digital goods (v1.1)
3. **Auto-refund**: Implemented webhook to trigger instant refund on failure
4. **Cancel order**: Moved cancel button to order detail top (was buried)
5. **More merchants**: Partnership team to onboard 10+ gift card merchants by launch

---

## Phase 3 — Usability Testing (20 Users, Pilot)

### NPS Score: 48
### SUS Score: 72/100

### Open Issues (Pre-Launch)

| Issue | Priority | Owner |
|---|---|---|
| Gift card redemption at physical stores needs QR scanner | High | Engineering |
| Vendor fulfillment timeout (30 min) causes unnecessary cancellations | Medium | Product |
| Promo code system not integrated with loyalty points | Low | Backend |
| Offline fallback for marketplace browsing | Medium | Mobile |
| Multiple currencies (USD for international products) | Low | Product |

### UAT Sign-off Criteria

| Criterion | Status |
|---|---|
| Syriatel top-up works end-to-end | ✅ |
| MTN top-up works end-to-end | ✅ |
| Gift card purchase + send via WhatsApp | ✅ |
| Digital goods purchase + code delivery | ✅ |
| Wallet hold + release flow | ✅ |
| Commission deduction + reporting | ✅ |
