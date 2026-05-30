# Remittance Design Tokens

Inherits all global design tokens from Design Language 2026. Additional remittance-specific tokens:

### Amount Input (Remittance)
```
RemittanceAmountInput:
  font-size: 40/44 (mobile), 52/56 (tablet)
  font-family: JetBrains Mono
  color: Dark
  currency-prefix-size: 20
  currency-selector-width: 80
  currency-selector-bg: Neutral 0
  currency-selector-border: Neutral 30
  cursor-color: Primary
  cursor-width: 2
```

### FX Rate Display
```
FXRateCard:
  background: Neutral 0
  border-radius: 12
  border-color: Neutral 20
  border-width: 1
  padding: 16
  rate-value-size: 18/24
  rate-value-weight: Bold
  rate-label-size: 12/16
  rate-label-color: Neutral 60
  mid-market-text-size: 12/16
  mid-market-text-color: Neutral 50
  spread-badge-bg: Warning 15%
  spread-badge-text: Warning (dark)
  spread-badge-text-size: 12
  spread-badge-radius: 6
  rate-lock-timer-size: 14/18
  rate-lock-timer-color: Primary
  countdown-warning-color: Error
```

### Beneficiary Card
```
BeneficiaryCard:
  height: 72
  padding: 12
  border-radius: 12
  background: Neutral 0
  avatar-size: 44
  avatar-border-radius: 22
  avatar-bg: Primary 20%
  name-size: 16/20
  name-weight: Medium
  relationship-size: 12/16
  relationship-color: Neutral 60
  last-sent-size: 12/16
  last-sent-color: Neutral 50
  action-button-size: 36
  action-button-radius: 18
  action-button-bg: Primary
  action-button-icon-color: White
```

### Fee Breakdown (Remittance)
```
RemittanceFeeBreakdown:
  background: Neutral 0
  border-radius: 12
  padding: 16
  row-height: 28
  label-size: 14/18
  label-color: Neutral 80
  value-size: 14/18
  value-weight: Medium
  fx-row-bg: Primary 5%
  fx-row-border-radius: 8
  total-label-size: 16/20
  total-value-size: 20/24
  total-value-weight: Bold
  recipient-gets-size: 16/20
  recipient-gets-weight: Bold
  recipient-gets-color: Primary
```

### Recurring Transfer Card
```
RecurringCard:
  height: 88
  padding: 14
  border-radius: 12
  background: Neutral 0
  border-left: 3px Primary
  amount-size: 18/24
  amount-weight: Bold
  frequency-size: 14/18
  frequency-color: Neutral 70
  next-date-size: 12/16
  next-date-color: Neutral 50
  status-icon-size: 20
  status-active-color: Success
  status-paused-color: Warning
  status-failed-color: Error
```

### Status Timeline (Transfer Detail)
```
StatusTimeline:
  dot-size: 12
  dot-active-color: Primary
  dot-completed-color: Success
  dot-pending-color: Neutral 40
  line-width: 2
  line-active-color: Primary
  line-completed-color: Success
  line-pending-color: Neutral 30
  label-size: 14/18
  label-active-weight: Medium
  label-completed-color: Success
  label-pending-color: Neutral 60
  timestamp-size: 12/16
  timestamp-color: Neutral 50
```

### Corridor Badge
```
CorridorBadge:
  height: 24
  padding: 8 (horizontal)
  radius: 12
  font-size: 12/16
  font-weight: Medium
  flag-icon-size: 16
  active-bg: Success 15%
  active-color: Success
  maintenance-bg: Warning 15%
  maintenance-color: Warning
  inactive-bg: Error 15%
  inactive-color: Error
```
