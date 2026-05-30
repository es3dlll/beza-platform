# Remittance Product Strategy

## Product Phases
```
Phase 1 (Months 1-3) — Core P2P MVP
  - Local P2P transfer (SYP) by phone number
  - Local P2P transfer (USD) by phone number
  - Multi-currency wallet support (SYP, USD)
  - Send money screen with contact book
  - Transaction history with filters
  - Transfer receipt (PDF)
  - Basic fee calculation (0.5% flat)

Phase 2 (Months 4-6) — Diaspora Remittance
  - Diaspora onboarding (EU passport/ID, proof of address)
  - USD→SYP remittance corridor (Germany, Sweden, Netherlands)
  - USD→USD same-currency (keep USD in recipient wallet)
  - FX engine integration with live rate display
  - Rate lock (60-second hold)
  - Beneficiary management (name, phone, relationship)
  - Delivery tracking (push notifications)
  - SMS pickup for unregistered recipients

Phase 3 (Months 7-9) — Recurring & Advanced
  - Recurring transfers (weekly, biweekly, monthly)
  - Request money (P2P payment requests)
  - EUR corridors (EUR→SYP, EUR→USD)
  - Scheduled future-dated transfers
  - Bulk transfer (one-to-many)
  - Transfer cancellation (30-min window)
  - Dispute management

Phase 4 (Months 10-12) — Scale & Compliance
  - 10+ corridor expansion (Turkey, UAE, Saudi, US, Canada, UK)
  - Source of funds upload for high-value senders
  - Step-up auth for large amounts
  - Correspondent banking integrations
  - API for partner corridor onboarding
  - Premium express corridors (EU fast-lane)
  - Loyalty integration (points per remittance)
```

## Feature Gating by KYC Level
| Feature | Level 0 (Basic) | Level 1 (ID) | Level 2 (Full KYC) | Level 3 (Enhanced) |
|---------|----------------|--------------|---------------------|---------------------|
| Receive SYP transfer | ✓ | ✓ | ✓ | ✓ |
| Receive USD transfer | ✓ | ✓ | ✓ | ✓ |
| Send SYP (daily) | 50,000 SYP | 500,000 SYP | 2,000,000 SYP | 5,000,000 SYP |
| Send USD (daily) | $50 | $500 | $2,000 | $10,000 |
| Diaspora send (USD) | ✗ | $200 | $1,000 | $5,000 |
| Diaspora recurring | ✗ | ✗ | ✓ | ✓ |
| Beneficiary management | ✗ | ✓ | ✓ | ✓ |
| Request money | ✗ | ✓ | ✓ | ✓ |
| FX conversion | ✗ | ✓ | ✓ | ✓ |
| Source of funds upload | ✗ | ✗ | ✗ | ✓ |
| Corridor: EU | ✗ | ✗ | ✓ | ✓ |
| Corridor: Turkey/UAE | ✗ | ✓ | ✓ | ✓ |
| Bulk transfer | ✗ | ✗ | ✓ | ✓ |

## Pricing Strategy
| Operation | Standard | Premium (5,000 SYP/mo) |
|-----------|----------|----------------------|
| Local P2P (SYP) | 0.5% (cap 5,000) | Free (first 30/mo) |
| Local P2P (USD) | 0.5% (cap $5) | Free (first 20/mo) |
| Diaspora remittance (USD→SYP) | 1.5% + 1.5% FX spread | 0.75% + 1% FX spread |
| Diaspora remittance (EUR→SYP) | 1.8% + 1.8% FX spread | 1.0% + 1.2% FX spread |
| Recurring transfer | 1.0% | 0.5% |
| Request money (payer) | 0.5% (cap 5,000 SYP) | Free |
| Transfer cancellation | Free (within 30 min) | Free |
| Express corridor (EU) | +0.5% surcharge | Included |

## FX Rate Strategy
| Corridor | Mid-Market Spread | Beza Rate | Competitor Rate |
|----------|------------------|-----------|-----------------|
| USD→SYP | 1.5% | 12,400 SYP/USD | 12,100 (hawala) |
| EUR→SYP | 1.8% | 13,200 SYP/EUR | 12,800 (hawala) |
| EUR→USD | 1.0% | 1.05 USD/EUR | 1.02 (bank) |
