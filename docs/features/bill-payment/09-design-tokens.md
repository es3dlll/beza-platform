# Bill Payment Design Tokens

Inherits all global design tokens from Design Language 2026. Additional bill-payment-specific tokens:

## Bill Category Grid
```
BillCategoryCard:
  width: 108 (3-column grid with 12pt gaps)
  height: 108
  border-radius: 16
  background: Neutral 0
  background-selected: Primary 5% opacity
  icon-size: 36
  label-size: 13/18
  label-color: Neutral 90
  badge-color: Primary
  badge-size: 10
  shadow: 0 2 8 rgba(0,0,0,0.06)
```

## Customer ID Input
```
CustomerIdInput:
  font-size: 24
  font-family: JetBrains Mono
  letter-spacing: 2
  color: Dark
  placeholder-color: Neutral 40
  caret-color: Primary
  border-color: Neutral 20
  border-color-focused: Primary
  border-color-error: Error
  border-radius: 12
  padding: 16
  height: 56
  helper-text-size: 12/16
  helper-text-color: Neutral 60
```

## Bill Detail Card
```
BillDetailCard:
  background: Neutral 0
  border-radius: 12
  padding: 16
  hero-amount-size: 40/48
  hero-amount-weight: Bold
  hero-amount-color: Dark
  label-size: 14/18
  label-color: Neutral 70
  value-size: 14/18
  value-weight: Medium
  value-color: Dark
  section-gap: 16
  row-gap: 12
```

## Late Fee Banner
```
LateFeeBanner:
  background: Error 10%
  border-color: Error
  border-radius: 10
  padding: 12
  icon-size: 20
  icon-color: Error
  text-size: 13/18
  text-color: Error
  amount-size: 16/22
  amount-weight: Bold
  amount-color: Error
```

## Status Badges (Bill Payment)
```
BillStatusBadge:
  height: 24
  padding: 8 (horizontal)
  radius: 12
  font-size: 12/16
  font-weight: Medium
  paid-bg: Success 15%
  paid-color: Success
  unpaid-bg: Warning 15%
  unpaid-color: Warning (dark)
  overdue-bg: Error 15%
  overdue-color: Error
  pending-bg: Info 15%
  pending-color: Info
  partial-bg: Accent 15%
  partial-color: Accent
```

## Receipt
```
Receipt:
  background: White
  border-radius: 12
  header-bg: Primary
  header-color: White
  header-size: 16/20
  watermark-opacity: 5%
  reference-size: 12/16
  reference-color: Neutral 60
  stamp-size: 80
  stamp-color: Primary (opacity 20%)
  action-button-radius: 10
```

## Biller Selection
```
BillerSelection:
  list-item-height: 64
  list-item-padding: 12
  icon-size: 32
  icon-radius: 8
  label-size: 15/20
  subtitle-size: 12/16
  subtitle-color: Neutral 60
  arrow-size: 20
  arrow-color: Neutral 40
```

## Reminder Settings
```
ReminderCard:
  background: Neutral 0
  border-radius: 12
  padding: 16
  toggle-width: 48
  toggle-height: 28
  toggle-active-bg: Primary
  toggle-inactive-bg: Neutral 30
  frequency-picker-height: 44
  frequency-picker-radius: 8
```
