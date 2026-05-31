# Loyalty Design Tokens

## Loyalty-Specific Tokens
Inherits all global design tokens from Beza Design Language 2026.

### Points Card
```
PointsCard:
  background: Primary (gradient: Primary → Primary Dark)
  points-size: 36/40 (mobile), 48/52 (tablet)
  points-weight: Bold
  points-color: White
  value-label-size: 14/18
  value-label-color: White (opacity 0.8)
  daily-earned-size: 12/16
  daily-earned-color: Success (light)
  cta-radius: 24
  cta-bg: White (opacity 0.2)
  cta-color: White
```

### Tier Display
```
TierBadge:
  height: 32
  padding: 8 12
  radius: 16
  font-size: 14/18
  font-weight: Bold
  bronze-bg: #CD7F32 (opacity 15%)
  bronze-color: #CD7F32
  silver-bg: #C0C0C0 (opacity 15%)
  silver-color: #808080
  gold-bg: #FFD700 (opacity 15%)
  gold-color: #B8860B
  platinum-bg: #E5E4E2 (opacity 15%)
  platinum-color: #2F4F4F

TierProgress:
  track-height: 12
  track-radius: 6
  track-bg: Neutral 20
  fill-gradient: Primary → Accent
  label-size: 12/16
  label-color: Neutral 70
  points-remaining-color: Primary
  points-remaining-weight: Bold
```

### Reward Item
```
RewardItem:
  card-bg: Neutral 0
  card-radius: 12
  card-padding: 16
  icon-size: 40
  title-size: 16/20
  title-weight: Medium
  point-cost-size: 14/18
  point-cost-weight: Bold
  point-cost-color: Primary
  value-syp-size: 12/16
  value-syp-color: Success
  cta-button: Primary
  cta-radius: 8
```

### Points Transaction
```
PointsTransactionItem:
  icon-size: 36
  earned-icon-bg: Success 10%
  earned-icon-color: Success
  redeemed-icon-bg: Error 10%
  redeemed-icon-color: Error
  expired-icon-bg: Neutral 30
  expired-icon-color: Neutral 60
  points-size: 16/22
  points-weight: Medium
  label-size: 14/20
  timestamp-size: 12/16
  timestamp-color: Neutral 60
```

### Tier Benefit List
```
TierBenefit:
  current-benefit-icon: Success
  current-benefit-color: Dark
  upgrade-benefit-icon: Warning (star)
  upgrade-benefit-color: Dark
  benefit-label-size: 14/18
  benefit-value-size: 14/18
  benefit-value-weight: Medium
  benefit-savings-size: 12/16
  benefit-savings-color: Success
```

### Celebration (Tier Upgrade)
```
TierCelebration:
  overlay-bg: rgba(0,0,0,0.6)
  card-bg: White
  card-radius: 20
  animation: Lottie (confetti or trophy)
  title-size: 24/32
  title-weight: Bold
  title-color: Primary
  subtitle-size: 16/20
  subtitle-color: Neutral 70
  cta-bg: Primary
  cta-color: White
```
