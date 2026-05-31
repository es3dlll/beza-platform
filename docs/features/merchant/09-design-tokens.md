# Merchant Design Tokens

## Merchant-Specific Tokens
Inherits all global design tokens from Design Language 2026. Additional merchant-specific tokens:

### Today's Sales Card
```
SalesCard:
  background: Success gradient (Success → Success Dark)
  amount-color: White
  amount-size: 42/48 (mobile), 54/60 (tablet)
  amount-weight: Bold
  label-color: White (opacity 0.8)
  label-size: 14/18
  trend-up-color: White (opacity 0.9)
  trend-down-color: Error (light)
  trend-icon-size: 16
  padding: 20
  border-radius: 16
```

### QR Code Display
```
QRDisplay:
  qr-size: 280 (mobile), 400 (tablet/display)
  qr-padding: 24
  qr-border-radius: 12
  qr-background: White
  frame-background: Primary (soft)
  business-name-size: 18/24
  business-name-color: Dark
  logo-size: 48
  logo-border-radius: 24
  brightness-boost-button: Icon + label
  brightness-boost-duration: 60s
```

### Payment Link Card
```
PaymentLinkCard:
  background: Neutral 0
  border-radius: 12
  padding: 16
  link-text-size: 12/16
  link-text-color: Primary
  amount-size: 20/26
  amount-weight: Bold
  description-size: 14/18
  description-color: Neutral 70
  expiry-color: Neutral 50
  expiry-size: 12/14
  share-button-height: 44
  share-button-icon-size: 20
```

### Transaction List (Merchant)
```
MerchantTransactionItem:
  icon-size: 40
  icon-border-radius: 20
  icon-color: Success (green dot for received)
  amount-size: 18/24
  amount-weight: Bold
  amount-color: Success (green — incoming)
  customer-phone-size: 14/18
  customer-phone-color: Neutral 70
  timestamp-size: 12/14
  timestamp-color: Neutral 50
  method-icon-size: 16  (QR/POS/Link badge)
```

### Settlement Card
```
SettlementCard:
  background: Info (soft 10% opacity)
  border: 1px solid Info 30%
  border-radius: 12
  padding: 16
  progress-bar-height: 6
  progress-bar-radius: 3
  progress-bar-bg: Neutral 20
  progress-bar-fill: Primary
  progress-label-size: 12/14
  net-amount-size: 24/30
  net-amount-weight: Bold
  mdr-detail-size: 12/14
  mdr-detail-color: Neutral 60
```

### MDR Badge
```
MDRBadge:
  height: 20
  padding: 6 (horizontal)
  radius: 10
  font-size: 10/12
  font-weight: Medium
  qr-bg: Primary 15%
  qr-color: Primary Dark
  pos-bg: Accent 15%
  pos-color: Accent Dark
  link-bg: Warning 15%
  link-color: Warning Dark
  web-bg: Info 15%
  web-color: Info Dark
```

### Verification Status
```
VerificationBadge:
  height: 24
  padding: 8 (horizontal)
  radius: 12
  font-size: 12/14
  verified-bg: Success 15%
  verified-color: Success
  pending-bg: Warning 15%
  pending-color: Warning
  rejected-bg: Error 15%
  rejected-color: Error
```
