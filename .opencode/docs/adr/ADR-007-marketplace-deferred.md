# ADR-007: Marketplace Deferred to V4+

## Status
Accepted

## Context
Marketplace (digital goods, e-commerce) was originally planned for V2-V3. Analysis revealed:
1. Marketplace depends on network effects from merchant density, user trust, and payment habits — none exist in V1
2. Syria has no dominant e-commerce platform post-Souq.com closure; building a marketplace from scratch requires logistics, vendor management, dispute resolution, and escrow
3. Beza's core value proposition in V1-V3 is financial inclusion (wallet, remittance, bills, payroll, merchant payments)
4. Every engineering hour spent on marketplace in V1-V3 is an hour NOT spent on the financial core

## Decision
Marketplace is DEFERRED to V4+ (24+ months from launch) with no pre-work in V1-V3.

## Consequences
- `features/marketplace/` directory kept for future reference but tagged as V4+
- No marketplace API, screens, or database work in V1-V3 Sprint plans
- Product roadmap updated: Marketplace removed from Tier D, moved to Tier E
- Escrow service also deferred (marketplace dependency)

## Compliance
- All Sprint plans in `execution/` must exclude marketplace
- Backlog grooming: reject any marketplace-related tickets
- Revisit only when Conditions Met:
  a) 100,000+ active wallets
  b) 5,000+ active merchants
  c) Daily transaction volume > 500M SYP
  d) Dedicated marketplace team available
