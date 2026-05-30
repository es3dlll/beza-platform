# Agent Network Design Tokens

## Agent-Specific Tokens
Inherits all global design tokens from Beza Design Language 2026. Additional agent-specific tokens:

### ActionButton (Cash-in / Cash-out)
```
ActionButton:
  width: 100% (parent)
  height: 64 (minimum — accommodates gloved hands)
  border-radius: 16
  font-size: 20/28
  font-weight: Bold
  icon-size: 32
  elevation: 2 (rest), 4 (pressed)
  animation: scale 1.05 on press (100ms)

  cash-in-bg: Success (#00A86B)
  cash-in-bg-gradient: Success → SuccessDark (#007B4D)
  cash-in-text: White
  cash-in-icon: 💵 or custom icon

  cash-out-bg: Error (#E53E3E)
  cash-out-bg-gradient: Error → ErrorDark (#B22222)  
  cash-out-text: White
  cash-out-icon: 💸 or custom icon

  disabled-bg: Neutral 20
  disabled-text: Neutral 50
```

### BalanceDisplay (Float)
```
FloatDisplay:
  amount-font-size: 48/52 (standard), 36/40 (compact)
  amount-font-family: JetBrains Mono
  amount-weight: Bold
  amount-color: Dark (normal)
  amount-color-low: Warning (100K-500K range)
  amount-color-critical: Error (<100K)
  amount-color-high: Success (>5M)
  currency-label-size: 18/22
  currency-label-color: Neutral 60
  background: Neutral 0
  background-gradient: Primary → PrimaryDark (at agent's option)
  padding: 24 (all sides)
  border-radius: 20
  last-updated-size: 12/16
  last-updated-color: Neutral 40
```

### StatusBadge (Agent-Specific Colors)
```
StatusBadge:
  height: 28
  padding: 12 (horizontal)
  radius: 14
  font-size: 14/18
  font-weight: Medium

  agent-active-bg: Success 15%
  agent-active-color: Success
  
  agent-pending-bg: Warning 15%
  agent-pending-color: Warning (dark)

  agent-suspended-bg: Error 15%
  agent-suspended-color: Error

  agent-terminated-bg: Neutral 20
  agent-terminated-color: Neutral 60

  offline-bg: Info 15%
  offline-color: Info

  float-ok-bg: Success 10%
  float-low-bg: Warning 10%
  float-critical-bg: Error 10%
```

### Receipt
```
Receipt:
  width: 58mm (thermal paper standard)
  font-family: monospace (Fira Code, size 10)
  header-size: 14 (bold)
  body-size: 10
  footer-size: 8
  separator: "─" repeated
  qr-code-size: 24x24mm
  barcode: CODE128 (transaction reference)
```

### Keypad (Numeric Entry)
```
Keypad:
  button-size: 56 (w+h)
  button-radius: 16  
  button-bg: Neutral 0
  button-bg-pressed: Neutral 10
  button-border: 1px Neutral 20
  font-size: 24/28
  font-family: JetBrains Mono
  font-weight: Medium
  delete-button-bg: Error 10%
  delete-button-color: Error
  confirm-button-bg: Primary
  confirm-button-color: White
```

### StepIndicator
```
StepIndicator:
  dot-size: 12
  dot-gap: 8
  active-dot-bg: Primary
  completed-dot-bg: Success
  upcoming-dot-bg: Neutral 20
  label-size: 12/16
  label-color: Neutral 60
```

### Bottom Tab Bar
```
AgentBottomTab:
  height: 64
  background: White
  border-top: 1px Neutral 10
  icon-size: 24
  icon-color: Neutral 40
  icon-active-color: Primary
  label-size: 10/14
  label-color: Neutral 40
  label-active-color: Primary
  badge-size: 18
  badge-color: Error
  tabs: الإيداع | السحب | الصندوق | العمليات
```

### Customer Verification
```
VerificationCode:
  digit-box-size: 56
  digit-box-radius: 12
  digit-box-bg: Neutral 0
  digit-box-border: 2px Neutral 20
  digit-box-active-border: 2px Primary
  digit-box-filled-border: 2px Success
  digit-box-error-border: 2px Error
  digit-font-size: 28
  digit-font-family: JetBrains Mono
  resend-timer-color: Neutral 40
  resend-active-color: Primary
```

### Transaction History (Agent View)
```
AgentTransactionItem:
  icon-size: 36
  icon-border-radius: 18
  cash-in-icon-bg: Success 15%
  cash-in-icon-color: Success
  cash-out-icon-bg: Error 15%
  cash-out-icon-color: Error
  float-funding-icon-bg: Info 15%
  float-funding-icon-color: Info
  commission-icon-bg: Accent 15%
  commission-icon-color: Accent
  amount-size: 16/20
  amount-weight: SemiBold
  amount-cash-in-color: Success
  amount-cash-out-color: Error
  label-size: 14/18
  timestamp-size: 12/16
  timestamp-color: Neutral 50
```

### Alert Banner
```
AlertBanner:
  height: 48
  padding: 12 horizontal
  font-size: 14/18
  font-weight: Medium
  info-bg: Info 10%
  info-color: Info
  warning-bg: Warning 10%
  warning-color: WarningDark
  error-bg: Error 10%
  error-color: Error
  offline-bg: Info 15%
  offline-color: Info
  icon-size: 20
  dismiss-button-size: 20
```
