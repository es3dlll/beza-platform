# Government Collections Product Strategy

## Product Vision
"Every government fee, payable in 30 seconds, with an official receipt — anywhere in Syria."

## Strategic Pillars

### 1. Ministry-First Integration
- Prioritise highest-volume government entities: Ministry of Finance (tax), Ministry of Interior (passport/civil), Ministry of Higher Education (tuition)
- Direct bilateral agreements with each ministry (not dependent on a central gateway)
- Parallel investment in central e-payment gateway if/when launched by Syrian government
- Each integration is a separate commercial agreement with defined SLAs and settlement terms

### 2. Zero-Effort User Experience
- Single search: citizen enters their Tax ID / passport number / licence plate → system identifies all payable obligations
- Saved payers: store frequently-used tax IDs, application numbers for one-tap payment
- Calendar integration: remind users of upcoming deadlines (tax filing, vehicle renewal, tuition)
- No account creation for one-time payments: guest payment flow with SMS receipt

### 3. Trust & Transparency
- Official government receipt with QR code — verifiable on ministry portal
- Real-time payment confirmation from ministry system
- Settlement tracking: user can see when their payment reached the ministry
- 100% money-back guarantee if payment fails to reach ministry within 48 hours

### 4. Omnichannel Access
- Mobile app (Flutter) — primary channel
- USSD for feature phones — important for rural areas
- Agent-assisted payments — for less tech-literate users
- Web portal for corporate/bulk payments
- API for third-party integrators (e.g., university portals embedding Beza payment)

## Release Roadmap

### Phase 1 (Months 1–6) — Core Foundation
- Income tax query and payment (Ministry of Finance)
- Property tax query and payment
- Traffic fine query and payment
- Official government receipt generation
- Basic payment history

### Phase 2 (Months 7–12) — Expansion
- Passport fee payment (Ministry of Interior)
- Vehicle registration and license renewal
- Civil registry certificate fees
- Reconciliation engine v1
- QR code receipt verification

### Phase 3 (Months 13–18) — Scale
- University tuition payments (top 5 universities)
- Court fee payments (Ministry of Justice)
- Municipality fee payments (top 5 cities)
- Bulk payment for businesses
- Corporate tax filing

### Phase 4 (Months 19–24) — Platform
- All 14 governorates municipality fees
- All public universities
- Ministry-specific portals embedded in Beza
- Government API marketplace
- Real-time settlement

## Success Criteria by Phase
| Phase | Ministries | Fee Types | Monthly Transactions | Revenue |
|-------|-----------|-----------|---------------------|---------|
| 1 | 2 | 3 | 10,000 | 50M SYP |
| 2 | 5 | 8 | 50,000 | 300M SYP |
| 3 | 10 | 15 | 200,000 | 1B SYP |
| 4 | 20 | 25 | 500,000 | 2.5B SYP |
