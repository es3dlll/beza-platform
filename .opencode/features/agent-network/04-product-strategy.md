# Agent Network Product Strategy

## Product Phases
```
Phase 1 (Months 1-4) — Core Agent Network
  - Agent registration with document upload and KYC
  - Agent POS app (Android) with:
    - Agent login using phone number + 6-digit PIN
    - Cash-in flow (user gives cash → agent debits float)
    - Cash-out flow (user requests cash → agent credits float)
    - Float balance display with real-time sync
    - Transaction history (last 100 txns, searchable)
    - Offline transaction queue with auto-sync
  - Agent float management:
    - Float top-up via Beza wallet transfer
    - Float monitoring dashboard (current, daily change, alerts)
  - Commission tracking (daily accrual, estimated monthly)
  - Bluetooth thermal receipt printing
  - Basic SMS notifications to customers

Phase 2 (Months 5-7) — Advanced Agent Tools
  - Agent tier system (Bronze: 1M SYP max cash-out per day → Platinum: 20M)
  - Agent locator (customer-facing map with status, queue length)
  - Agent-to-agent float transfer (emergency float sharing)
  - Multi-currency float (SYP + USD)
  - Agent messaging (in-app announcements, limit alerts)
  - QR-based customer identification (no phone number entry)
  - USSD fallback for customer verification when POS offline

Phase 3 (Months 8-10) — Intelligence & Optimization
  - Agent performance dashboard (volume trends, commission history, rankings)
  - Automated float recommendations (ML-based restocking suggestions)
  - Customer satisfaction tracking (post-txn SMS survey)
  - Agent dispute management (customer claims, evidence upload)
  - Fraud detection (velocity checks, unusual pattern alerts)
  - Agent referral program (bonus for recruiting new agents)

Phase 4 (Months 11-12) — Scale & Ecosystem
  - Predictive float restocking (ML demand forecasting per agent)
  - Automated float rebalancing (surplus agents → deficit agents)
  - Agent lending (float-backed micro-loans to agents)
  - Agent analytics API (for distributors, partners)
  - Bulk transaction processing (salary disbursements via agents)
  - Agent network health dashboard (national, regional, district views)
```

## Feature Gating by Agent Tier
| Feature | Bronze | Silver | Gold | Platinum |
|---------|--------|--------|------|----------|
| Cash-in | ✓ | ✓ | ✓ | ✓ |
| Cash-out | ✓ | ✓ | ✓ | ✓ |
| Float top-up (daily max) | 3M SYP | 10M SYP | 25M SYP | Unlimited |
| Max cash-out per txn | 500K SYP | 2M SYP | 5M SYP | 10M SYP |
| Max cash-out per day | 2M SYP | 5M SYP | 15M SYP | 40M SYP |
| Max cash-in per day | 5M SYP | 10M SYP | 30M SYP | 50M SYP |
| Max float balance | 5M SYP | 15M SYP | 50M SYP | Unlimited |
| Commission rate (cash-out) | 0.5% | 0.6% | 0.75% | 1.0% |
| Commission rate (cash-in) | 0.3% | 0.4% | 0.5% | 0.6% |
| Priority support | Standard | Standard | Priority | 24/7 VIP |
| POS device | Leased (basic) | Leased (pro) | Owned (after 12mo) | Free upgrade |
| Free float insurance coverage | 0 | 1M SYP | 3M SYP | 10M SYP |
| Agent-to-agent transfer | ✗ | ✓ (up to 1M) | ✓ (up to 5M) | ✓ (unlimited) |
| Training resources | Basic | Standard | Premium | Personal coach |
| Monthly fee | Free | Free | 25,000 SYP | 50,000 SYP |

## Pricing Strategy
| Operation | User Pays | Agent Earns | Beza Retains |
|-----------|-----------|-------------|--------------|
| Cash-in (deposit) | Free | 0.5% of amount | -0.5% (cost) |
| Cash-out (withdrawal) | 1.5% (cap 15,000 SYP) | 0.75% | 0.75% |
| Float top-up from wallet | Free | N/A | N/A |
| Float top-up from bank | 0.5% | N/A | 0.5% |
| POS device (monthly) | N/A | 5,000 SYP (paid by agent) | 5,000 SYP |
| Agent premium tier | N/A | 25,000-50,000 SYP/mo | 25,000-50,000 SYP |
