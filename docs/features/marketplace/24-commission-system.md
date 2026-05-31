# Commission System

## Commission Rate Structure

### By Category

| Category | Base Rate | Premium Tier | Enterprise Tier |
|---|---|---|---|
| Mobile Top-Up | 3% | 4% | 5% |
| Internet Packages | 5% | 6% | 7% |
| Gift Cards | 8% | 9% | 10% |
| Digital Goods | 12% | 13% | 15% |
| Bill Payment | 2% | 3% | 4% |

### Tier Qualification

| Tier | Monthly Volume Threshold | Requirements |
|---|---|---|
| Standard | < 500,000 SYP | All vendors start here |
| Premium | 500,000 - 2,000,000 SYP | 4.5+ rating, < 1% refund rate |
| Enterprise | > 2,000,000 SYP | 4.7+ rating, < 0.5% refund rate, 6+ months active |

## Commission Calculation

```
Commission = ItemPrice * CommissionRate

Example:
  Item: PUBG 600 UC
  Price: 28,000 SYP
  Category: Digital Goods (Base Rate: 12%)
  Vendor Volume: 1,200,000 SYP/month (Premium Tier) -> 13%
  
  Commission = 28,000 * 0.13 = 3,640 SYP
  Vendor Earnings = 28,000 - 3,640 = 24,360 SYP
```

## Commission Lifecycle

1. **ACCRUED**: Calculated at order confirmation
2. **SETTLED**: After fulfillment/delivery confirmation (24h cool-down)
3. **PAID**: Transferred to vendor on payout schedule
4. **REVERSED**: If refund is issued after settlement

## Payout Schedule

| Product Type | Payout Timing | Hold Period |
|---|---|---|
| Digital (instant) | T+1 day | 24h after fulfillment |
| Gift cards | T+7 days | After redemption or 30 days |
| Physical goods | T+7 days after delivery confirmation | 7 days |
| Mobile top-up | N/A (spread model, not commission) | - |

## Payout Methods

| Method | Fee | Processing Time |
|---|---|---|
| Beza Wallet | Free | Instant |
| Bank Transfer (local) | 1,000 SYP | 1-3 business days |
| Bank Transfer (international) | 5 USD | 3-7 business days |

## Minimum Payout

- **Standard vendors**: 50,000 SYP minimum
- **Premium vendors**: 25,000 SYP minimum
- **Enterprise vendors**: No minimum

## Tax Withholding

- 5% withholding tax applied to commission amounts for registered vendors (tax ID provided)
- 10% withholding for unregistered vendors (until tax ID provided)
- Monthly tax reports generated for finance team
