# Loyalty UX Strategy

## Design Principles
1. **Points are money** — 1 point = 1 SYP, displayed with same prominence as wallet balance
2. **Progress is visible** — Always show tier progress, next milestone, fee savings
3. **Celebrate milestones** — Tier upgrades are special moments (animation, notification, confetti)
4. **Arabic-first** — Tier names (برونز، فضي، ذهبي، بلاتيني), points terminology
5. **Simple math** — No confusing earning formulas; "1 point per 1,000 SYP"
6. **Instant gratification** — Points appear immediately after transaction
7. **Merchant campaigns feel exclusive** — "Offer for you" personalization

## Information Architecture
```
Loyalty Feature (in Wallet App):
  ├── Points Balance (in Wallet Home header)
  │   ├── Current points: "12,500 نقطة"
  │   ├── Tier badge: "فضي"
  │   └── Tap → Loyalty Hub
  │
  ├── Loyalty Hub
  │   ├── Points Card
  │   │   ├── Points balance
  │   │   ├── SYP equivalent value
  │   │   └── Points earned today/this month
  │   ├── Tier Card
  │   │   ├── Current tier (with icon + color)
  │   │   ├── Progress to next tier
  │   │   ├── Benefits summary
  │   │   └── "How to upgrade" link
  │   ├── Rewards Catalog
  │   │   ├── Fee Discounts
  │   │   ├── Airtime
  │   │   ├── Gift Cards
  │   │   └── Partner Offers (external)
  │   ├── Points History
  │   │   ├── Points earned (green)
  │   │   ├── Points redeemed (red)
  │   │   └── Points expired (grey)
  │   └── Merchant Campaigns
  │       ├── Active offers near me
  │       └── My campaigns (merchant view)
  │
  └── Transaction Detail (updated)
      ├── Points earned: "25 نقطة"
      └── Cumulative: "إجمالي النقاط: 12,500"
```

## Key Screens & Their Goals

### Loyalty Hub (Home)
- **Business Goal**: Drive engagement, increase transactions to earn more points
- **Psychological Goal**: User feels rewarded, valued, motivated to transact more
- **Trust Signal**: Real-time points balance, clear value (1 pt = 1 SYP), progress bar
- **Layout**: Points hero card, tier card, featured rewards, recent activity

### Tier Progress Screen
- **Business Goal**: Incentivize users to increase transaction volume
- **Psychological Goal**: "I'm almost there!" — gamification of financial behavior
- **Layout**: Current tier icon + next tier icon, progress bar with exact points needed
- **CTA**: "How to earn faster" → shows best earning activities

### Rewards Catalog
- **Business Goal**: Redeem points (reduce liability, create partner revenue)
- **Psychological Goal**: "My points are valuable — I can get real things"
- **Layout**: Categories → items with point cost and SYP value
- **Trust Signal**: "قيمة 15,000 ل.س" next to point cost

## Points Display States
| State | Visual | Action |
|-------|--------|--------|
| Normal balance | "15,000 نقطة" (with SYP value) | View catalog |
| Zero balance | "0 نقطة — ابدأ بكسب النقاط!" | View earning tips |
| Just earned | Animated counter + "+25 نقطة" | Tap to see breakdown |
| Near tier upgrade | Pulsing progress bar + "على بعد 500 نقطة!" | View tier benefits |
| Points expiring | Warning: "5,000 نقطة ستنتهي قريباً" | Redeem now |
| Tier upgraded | Confetti + "🎉 مبروك! المستوى الذهبي" | Explore benefits |
