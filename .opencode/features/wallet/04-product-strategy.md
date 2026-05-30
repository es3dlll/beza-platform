# Wallet Product Strategy

## Product Phases
```
Phase 1 (Months 1-3) — Core Wallet MVP
  - Register/login with phone number + PIN
  - Multi-currency wallet (SYP, USD)
  - P2P transfer (by phone number)
  - Balance check (app + USSD *123#)
  - Transaction history
  - Agent cash-in/cash-out
  - Airtime top-up

Phase 2 (Months 4-6) — Payments & Bills
  - Bill payment (electricity, water, telecom)
  - QR payment (merchant-presented)
  - Payment links
  - Recurring transfers
  - Request money
  - Transaction receipts (PDF)

Phase 3 (Months 7-9) — Advanced Features
  - Savings goals
  - Round-up savings
  - Virtual prepaid card
  - Spending insights
  - Transaction search + filters
  - Monthly statements

Phase 4 (Months 10-12) — Growth & Monetization
  - Physical prepaid card
  - Premium accounts
  - Loyalty program integration
  - Card-to-card transfers
  - Scheduled transactions
  - Export to accounting apps
```

## Feature Gating by KYC Level
| Feature | Level 0 (Basic) | Level 1 (ID) | Level 2 (Full KYC) |
|---------|----------------|--------------|---------------------|
| Balance Check | ✓ | ✓ | ✓ |
| Receive money | ✓ | ✓ | ✓ |
| Send money (daily) | 50,000 SYP | 500,000 SYP | 2,000,000 SYP |
| Agent cash-in (daily) | 100,000 SYP | 1,000,000 SYP | 5,000,000 SYP |
| Agent cash-out (daily) | 50,000 SYP | 500,000 SYP | 2,000,000 SYP |
| Wallet balance (max) | 200,000 SYP | 2,000,000 SYP | 10,000,000 SYP |
| Bill payment | ✗ | ✓ | ✓ |
| Card issuance | ✗ | ✗ | ✓ |
| Savings goals | ✗ | ✓ | ✓ |
| Recurring transfers | ✗ | ✗ | ✓ |

## Pricing Strategy
| Operation | Standard | Premium (5,000 SYP/mo) |
|-----------|----------|----------------------|
| P2P Transfer | 0.5% (cap 5,000) | Free (first 20/mo) |
| Cash-in | Free | Free |
| Cash-out | 1.5% (cap 10,000) | 0.5% (cap 5,000) |
| Bill Payment | Free | Free |
| Card Issuance | 5,000 SYP | Free |
| ATM Withdrawal | 2,000 SYP/txn | Free (first 5/mo) |
| FX Spread | 3% | 1.5% |
