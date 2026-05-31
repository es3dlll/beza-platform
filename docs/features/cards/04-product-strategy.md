# Cards Product Strategy

## Product Phases
```
Phase 1 (Months 1-3) — Virtual Card MVP
  - Instant virtual card issuance (BIN sponsorship via local switch)
  - Card freeze/unfreeze
  - Online payment (CVV, expiry)
  - Spending limits: online category only
  - Real-time transaction notifications (push)
  - Transaction history
  - PIN set/change for online auth

Phase 2 (Months 4-6) — Apple Pay / Google Pay
  - Tokenization service integration
  - Add card to Apple Wallet & Google Wallet
  - NFC contactless payments via phone
  - One-time virtual card (privacy.com model)
  - Spending limits: POS category
  - Enhanced fraud monitoring

Phase 3 (Months 7-9) — Physical Card
  - Plastic card manufacturing & personalization
  - Agent-based card delivery & activation
  - ATM withdrawal capability
  - PIN management for ATM/POS
  - Card replacement workflow
  - Spending limits: ATM, international categories

Phase 4 (Months 10-12) — International & Scale
  - International BIN sponsorship (Mastercard/Visa)
  - Cross-border transactions
  - Multi-currency card (SYP + USD)
  - Premium card accounts (higher limits, dedicated support)
  - Card-to-card transfers
  - Recurring card payments
```

## Feature Gating by KYC Level
| Feature | Level 1 (ID) | Level 2 (Full KYC) | Level 3 (Enhanced) |
|---------|--------------|---------------------|---------------------|
| Virtual card issuance | ✓ | ✓ | ✓ |
| Physical card | ✗ | ✓ | ✓ |
| Online spending (daily) | 200,000 SYP | 1,000,000 SYP | 5,000,000 SYP |
| POS spending (daily) | 100,000 SYP | 500,000 SYP | 2,000,000 SYP |
| ATM withdrawal (daily) | ✗ | 200,000 SYP | 1,000,000 SYP |
| International spending | ✗ | ✗ | ✓ |
| One-time card | ✓ | ✓ | ✓ |
| Apple Pay / Google Pay | ✓ | ✓ | ✓ |
| Card balance (max) | 500,000 SYP | 3,000,000 SYP | 10,000,000 SYP |
| Multiple cards | 2 | 5 | 10 |

## Pricing Strategy
| Operation | Standard | Premium (5,000 SYP/mo) |
|-----------|----------|----------------------|
| Virtual card issuance | 5,000 SYP | Free (first 2/year) |
| Physical card issuance | 15,000 SYP | Free (first 1/year) |
| Card replacement | 10,000 SYP | Free |
| ATM withdrawal | 2,000 SYP + 0.5% | Free (first 5/mo) |
| Online purchase (domestic) | Free | Free |
| Online purchase (international) | 2% FX fee | 1% FX fee |
| Monthly maintenance | Free | Included |
| One-time virtual card | 2,000 SYP | 1,000 SYP |
| SMS notification | Free | Free |
