# Cards Design Tokens

Inherits all global design tokens from Design Language 2026. Additional cards-specific tokens:

### Card Visual
```
CardVisual:
  width: 312 (mobile), 400 (tablet)
  height: 196 (mobile), 252 (tablet)
  border-radius: 16
  background-gradient: Primary → Primary Dark (default)
  background-frozen: Neutral 30 (greyed out)
  background-one-time: Accent gradient
  network-logo-height: 24
  network-logo-position: top-right, padding 16
  pan-font: JetBrains Mono
  pan-size: 20/24
  pan-color: White
  pan-masked-char: "●"
  last-four-size: 24/28
  expiry-label: "VALID THRU"
  expiry-size: 12/14
  cardholder-name-size: 14/16
  card-balance-size: 16/20
  padding: 16 (internal)
  shadow: elevation 4 (Android), shadow 6 (iOS)
```

### Quick Actions
```
CardActionGrid:
  columns: 2 (mobile), 4 (tablet)
  button-height: 72
  button-radius: 12
  button-bg: Neutral 0
  button-bg-pressed: Primary 10%
  icon-size: 24
  label-size: 12/16
  label-color: Dark
  gap: 12
```

### Card Carousel
```
CardCarousel:
  viewport-height: 220 (mobile), 280 (tablet)
  card-gap: 16 (between cards)
  page-indicator-size: 8
  page-indicator-active-color: Primary
  page-indicator-inactive-color: Neutral 30
  card-scale: 1.0 (center), 0.9 (adjacent)
```

### Card Detail Display
```
CardDetail:
  pan-display-size: 28/32
  pan-display-font: JetBrains Mono
  pan-masked: true (show only after biometric)
  cvv-display-size: 18/22
  cvv-hidden-char: "•••"
  expiry-display-size: 16/20
  detail-row-height: 44
  detail-label-size: 14/16
  detail-label-color: Neutral 60
  detail-value-size: 14/16
  detail-value-weight: Medium
```

### Limit Sliders
```
LimitSlider:
  track-height: 6
  track-radius: 3
  track-bg: Neutral 20
  track-active-color: Primary
  thumb-size: 24
  thumb-color: White
  thumb-border: Primary 2px
  value-label-size: 14/18
  value-label-weight: Bold
  category-icon-size: 20
  category-label-size: 14/16
```

### Status Badges (Card-Specific)
```
CardStatusBadge:
  height: 20
  padding: 6 (horizontal)
  radius: 10
  font-size: 11/14
  font-weight: Medium
  active-bg: Success 15%
  active-color: Success
  frozen-bg: Warning 15%
  frozen-color: Warning
  closed-bg: Neutral 20
  closed-color: Neutral 60
  lost-bg: Error 15%
  lost-color: Error
  expired-bg: Neutral 20
  expired-color: Neutral 60
```

### One-Time Card
```
OneTimeCard:
  background: Accent gradient (teal)
  timer-size: 14/18
  timer-color: White (opacity 0.8)
  destroy-badge-bg: Error
  destroy-badge-text: "تم التدمير"
  countdown-color: Warning
```
