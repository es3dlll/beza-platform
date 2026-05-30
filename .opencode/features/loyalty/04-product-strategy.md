# Loyalty Product Strategy

## Product Phases
```
Phase 1 (Months 1-2) — Points Engine
  - Points earning on all transactions
  - Points balance display in wallet
  - Points transaction history
  - Automatic tier assignment based on 12-month rolling total
  - Bronze and Silver tiers active

Phase 2 (Months 3-4) — Redemption & Tiers
  - Reward catalog (fee discounts, airtime, gift cards)
  - Points redemption flow
  - Gold and Platinum tiers active
  - Tier benefits applied (reduced fees, higher limits)
  - Tier upgrade/downgrade notifications

Phase 3 (Months 5-6) — Advanced Features
  - Partner offers in reward catalog
  - Referral bonus points
  - Anniversary rewards
  - Points expiry (12-month rolling)
  - Tier downgrade warning (30-day notice)

Phase 4 (Months 7-12) — Merchant Loyalty
  - Merchant campaign creation portal
  - Custom campaign rewards (merchant-funded)
  - Campaign analytics dashboard
  - Points partnership with merchants
  - White-label loyalty for enterprise merchants
```

## Points Economics
```
Earning Rates:
  P2P Transfer (send):        1 pt / 1,000 SYP
  P2P Transfer (receive):     1 pt / 1,000 SYP
  Bill Payment:               1 pt / 1,000 SYP
  Agent Cash-in:              0.5 pt / 1,000 SYP
  Agent Cash-out:             0.5 pt / 1,000 SYP
  Airtime Top-up:             1 pt / 1,000 SYP
  Savings Deposit:            2 pt / 1,000 SYP
  Referral Bonus:             5,000 pts (one-time)
  Anniversary Bonus:          1,000 pts × tier multiplier

Tier Multipliers:
  Bronze:  1.0x  (standard rate)
  Silver:  1.2x
  Gold:    1.5x
  Platinum: 2.0x

Point Value:
  1 point = 1 SYP in redemption value
  Minimum redemption: 1,000 points
  Maximum redemption: No limit (subject to balance)
```

## Tier Qualification
```
12-Month Rolling Points Earned:
  Bronze:    0+ points (automatic enrollment)
  Silver:    10,000+ points
  Gold:      50,000+ points
  Platinum:  200,000+ points

Qualification Check: Daily batch job recalculates
Grace Period: Maintain tier for 30 days after falling below threshold
Downgrade: Move to next lower tier that user qualifies for
```

## Tier Benefits
| Benefit | Bronze | Silver | Gold | Platinum |
|---------|--------|--------|------|----------|
| Transfer fee | 0.5% | 0.4% | 0.3% | 0.2% |
| Cash-out fee | 1.5% | 1.2% | 1.0% | 0.5% |
| Daily send limit | 500K | 1M | 2M | 5M |
| Daily cash-out limit | 500K | 1M | 2M | 5M |
| Wallet max balance | 2M | 5M | 10M | 25M |
| FX spread discount | 0% | 10% off | 20% off | 30% off |
| Support priority | Standard | Priority | Priority | VIP |
| Birthday reward | — | 500 pts | 1,000 pts | 2,500 pts |
| Exclusive offers | — | Monthly | Weekly | Daily |
