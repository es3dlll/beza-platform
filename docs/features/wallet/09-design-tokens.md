# Wallet Design Tokens

## Wallet-Specific Tokens
Inherits all global design tokens from Design Language 2026. Additional wallet-specific tokens:

### Balance Card
```
BalanceCard:
  background: Primary (gradient: Primary → Primary Dark)
  background-disabled: Neutral 10
  amount-color: White
  amount-size: 36/40 (mobile), 48/52 (tablet)
  amount-weight: Bold
  currency-label-color: White (opacity 0.8)
  currency-label-size: 14/18
  hidden-amount-char: "●"
  quick-action-icon-size: 24
  quick-action-button-size: 56 (w+h)
  quick-action-button-radius: 16
  quick-action-button-bg: White (opacity 0.2)
  fx-ticker-color: White (opacity 0.7)
  fx-ticker-size: 12/16
```

### Transaction List
```
TransactionItem:
  icon-size: 40
  icon-border-radius: 20
  icon-type-colors:
    received: Success (bg: Success 10% opacity)
    sent: Error (bg: Error 10% opacity)
    bill: Info (bg: Info 10% opacity)
    cash-in: Success (bg: Success 10%)
    cash-out: Warning (bg: Warning 10%)
    card: Accent (bg: Accent 10%)
  amount-size: 16/22
  amount-weight: Medium
  label-size: 14/20
  timestamp-size: 12/16
  timestamp-color: Neutral 60
  status-dot-size: 8
```

### Amount Input
```
AmountInput:
  font-size: 36/40
  font-family: JetBrains Mono
  color: Dark
  placeholder-color: Neutral 40
  currency-prefix-size: 18
  cursor-color: Primary
  cursor-width: 2
```

### Quick Actions Grid
```
QuickAction:
  grid-columns: 4 (mobile), 6 (tablet)
  button-size: 64
  icon-size: 28
  label-size: 12/16
  label-color: Dark
  button-bg: Neutral 0
  button-bg-pressed: Primary 10% opacity
  button-radius: 16
```

### Fee Breakdown
```
FeeBreakdown:
  background: Neutral 0
  border-radius: 12
  padding: 16
  row-height: 24
  label-size: 14/18
  label-color: Neutral 80
  value-size: 14/18
  value-weight: Medium
  total-label-size: 16/20
  total-value-size: 18/22
  total-value-weight: Bold
```

### Status Badges
```
StatusBadge:
  height: 24
  padding: 8 (horizontal)
  radius: 12
  font-size: 12/16
  font-weight: Medium
  completed-bg: Success 15%
  completed-color: Success
  pending-bg: Warning 15%
  pending-color: Warning (dark)
  failed-bg: Error 15%
  failed-color: Error
  disputed-bg: Error 10%
  disputed-color: Error
  refunded-bg: Info 15%
  refunded-color: Info
```
