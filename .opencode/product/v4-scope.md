# V4 Scope — Beza Platform Expansion (Tier E — Super App Ecosystem)

**Target:** Months 25–36 | **Products:** Marketplace (M1–M4), Escrow, Takaful, Investments

**Prerequisite:** V3 production-stable, ADR-007 Go/No-Go gates (100K+ wallets, 5K+ merchants, 500M+/day volume)

---

## V4 TIER E (Month 25–36)

### 0 — بوابة ADR-007 Go/No-Go
- [x] Go/No-Go assessment document created
- [ ] All gates met (G1: 100K wallets, G2: 5K merchants, G3: 500M SYP/day, G4: dedicated team)
- [ ] Management sign-off obtained
- [ ] Fallback: Takaful/Investments only if gates fail

### 1 — محاذاة V4 والامتثال
- [x] 05-v4-scope.md created
- [ ] Feature bibles: takaful/, investments/ (15 files each minimum)
- [ ] openapi.yaml V4 tags: marketplace, escrow, takaful, investments
- [ ] Ledger impact matrix — escrow holds, commission income, premium flows
- [ ] Ministry of Economy e-commerce registration
- [ ] CBS insurance license
- [ ] Capital Market Authority license / partnership
- [ ] Sharia Board approval for Takaful + investment funds
- [ ] CI modules: marketplace, escrow, takaful, investments
- [ ] Feature flags: marketplace_m1, physical_goods, takaful, investments

### 2 — Escrow (أسابيع 3–5)
- [ ] Agreement model: buyer, seller, amount, milestones, expiry
- [ ] CFE: hold → release / refund via suspense (2700)
- [ ] Fee: 1% capped 50K SYP (B2C)
- [ ] Dispute: open case → Admin resolution → release/refund
- [ ] Available for Merchant B2B + Marketplace M3
- [ ] Events: EscrowCreated, EscrowReleased, EscrowDisputed
- [ ] Admin: dispute queue
- [ ] E2E: escrow B2B via CFE

### 3 — Marketplace M1: Digital (أسابيع 6–14)
- [ ] Migrations: vendors, products, categories, orders, order_items, fulfillments
- [ ] Catalog service: CRUD digital products, pricing, inventory
- [ ] Order state machine: cart → paid → fulfilling → completed / failed / refunded
- [ ] Payment: CFE hold → fulfill → capture; fail → release
- [ ] Syriatel/MTN top-up adapter (reuse Bills + marketplace commission)
- [ ] Digital goods pipeline: game credits, streaming codes — queue + retry
- [ ] Weekly settlement with operators
- [ ] Vendor invite-only onboarding: KYC, commission contract
- [ ] Vendor portal v1: products, orders, sales reports
- [ ] Commission: 8–15% configurable per category → 3100 Fee Income
- [ ] Fraud rules: velocity purchasing, chargeback pattern
- [ ] Dispute resolution (journeys/08-dispute-resolution)
- [ ] E2E: buy 10,000 SYP top-up from Marketplace tab

### 4 — Marketplace M2–M4 (أسابيع 15–32)
- [ ] Gift cards: purchase, SMS/WhatsApp send, QR redeem
- [ ] Merchant redemption portal
- [ ] Loyalty V2 integration: points on marketplace purchases
- [ ] Promo codes v2 engine
- [ ] Physical products: inventory, shipping addresses (14 governorates)
- [ ] 3PL logistics adapter
- [ ] COD: agent/delivery guy collection
- [ ] Shipment tracking + status notifications
- [ ] Self-serve vendor registration
- [ ] Mandatory escrow for orders > 500K SYP
- [ ] Marketplace API (Open Finance): catalog, order, webhook fulfillment
- [ ] Rate limits separate from core wallet

### 5 — Takaful (أسابيع 8–16, parallel)
- [ ] Health basic, device insurance, travel insurance
- [ ] Contribution (subscription) + tabarru' pool — no riba
- [ ] Simplified underwriting V4
- [ ] Migration: policies, claims, premium debit from wallet
- [ ] CFE: premium → pool account; claim payout from pool
- [ ] Insurance partner API / daily batch
- [ ] Mobile: explore, subscribe, policy, claim upload
- [ ] Admin: claim approval, loss ratio reports
- [ ] E2E: subscribe + claim mock approved

### 6 — Investments (أسابيع 10–18, parallel)
- [ ] Sharia-compliant funds (Sukuk-like, equity via partner)
- [ ] Minimum investment — Tier 3 only
- [ ] No derivatives, no short, no crypto
- [ ] Migration: subscribe/redeem units, T+2 settlement
- [ ] Daily NAV from fund partner
- [ ] CFE: debit wallet → investment liability account
- [ ] Zakat calculator integration (estimated)
- [ ] Mobile: NAV display, subscribe, redeem, history
- [ ] Admin: fund partner reconciliation
- [ ] E2E: subscribe 100K SYP + partial redeem

### 7 — Flutter V4 (أسابيع 30–34)
- [ ] Marketplace tab in bottom nav or More → promoted
- [ ] Screens: home categories, product detail, cart, checkout, order tracking, gift send
- [ ] Takaful flows: explore, subscribe, policy, claim upload
- [ ] Investments flows: NAV, subscribe, redeem, history
- [ ] All flows with 2026 design + AR risk disclosure
- [ ] Vendor app (optional separate Flutter: vendor_app/)
- [ ] All M1+M2 routes in production build

### 8 — Admin V4 + بوابات (أسابيع 35–36)
- [ ] Marketplace: vendors, product moderation, orders, commissions, settlements
- [ ] Escrow disputes queue
- [ ] Takaful: policies, claims
- [ ] Investments: NAV, subscription queue
- [ ] Analytics: GMV, AOV, conversion
- [ ] Vendor portal: vendor.beza.app (separate from admin)

### 9 — QA والأمان (أسبوع 37)
- [ ] Tests: order lifecycle, escrow, commission math
- [ ] E2E: top-up, gift card, physical COD, takaful subscribe, investment subscribe
- [ ] Regression V1–V3 smoke
- [ ] Pen test: marketplace checkout + vendor portal
- [ ] Supply chain: vendor API key rotation
- [ ] Performance: 1000 orders/hour digital, catalog search < 200ms p95

### 10 — إطلاق V4 (أسبوع 38)
- [ ] Phased: Takaful (limited) + Investments (Tier 3 invite) if licenses ready
- [ ] Marketplace M1 → M2 → M3 by feature flags
- [ ] Escrow for B2B merchants before consumer physical goods
- [ ] KPIs: GMV 750M SYP/month, MAU buyers 50K, digital delivery >99.5%
- [ ] 14 days production per enabled product
- [ ] ADR gates continuous for M3
- [ ] V5 kickoff: regional expansion, social commerce

---

## NOT IN V4 (V5+)

| Item | Reason |
|------|--------|
| Crypto / USDT / NFT | Regulatory rejection |
| P2P gift card trading | Fraud risk |
| Social commerce / group buying | V5+ roadmap |
| Regional expansion (Lebanon/Iraq) | V5+ |
| P2P lending between strangers | Permanent exclusion |

---

## V4 KPIs

| Metric | Target | Measurement |
|--------|--------|-------------|
| Marketplace GMV | 750M SYP/month by Y1 | Monthly gross volume |
| MAU buyers | 50,000 by Y1 | Unique monthly buyers |
| Digital delivery rate | >99.5% | Successful / total digital orders |
| Takaful policies in force | 5,000 by Y1 | Active policies |
| Investments AUM | SYP 500M by Y1 | Total fund balance |
| Vendor satisfaction | >4.0/5.0 | Quarterly survey |
