# FX Engine Design Tokens

Inherits all global design tokens from Design Language 2026. Additional FX-specific tokens:

### Rate Card
```
RateCard:
  background: Neutral 0
  border-radius: 16
  padding: 16
  shadow: 0 2 8 rgba(0,0,0,0.08)
  rate-size: 32/36 (mobile), 40/44 (tablet)
  rate-weight: Bold
  rate-font: JetBrains Mono
  rate-color: Dark
  label-size: 12/16
  label-color: Neutral 60
  bid-color: Success
  ask-color: Error
  last-updated-size: 11/14
  sparkline-height: 32
  sparkline-width: 80
  source-dot-size: 8
  source-dot-online: Success
  source-dot-offline: Neutral 40
  source-dot-stale: Warning
```

### Rate Lock Timer
```
RateLockTimer:
  size: 48 (circular)
  stroke-width: 4
  color-green: #22C55E (healthy, >15s)
  color-amber: #F59E0B (<10s)
  color-red: #EF4444 (<5s)
  animation-duration: 500ms (pulse on lock)
  countdown-font: JetBrains Mono
  countdown-size: 14
  countdown-weight: Bold
```

### Rate Source Badge
```
RateSourceBadge:
  height: 22
  padding: 8 (horizontal)
  radius: 11
  font-size: 11/14
  official-bg: Blue 15%
  official-color: Blue
  parallel-bg: Purple 15%
  parallel-color: Purple
  blackmarket-bg: Orange 15%
  blackmarket-color: Orange dark
  beza-bg: Primary 15%
  beza-color: Primary
```

### Conversion Preview
```
ConversionPreview:
  background: Neutral 0
  border-radius: 12
  padding: 16
  row-height: 28
  label-size: 14/18
  label-color: Neutral 80
  value-size: 14/18
  value-weight: Medium
  value-font: JetBrains Mono
  rate-size: 16/20
  rate-weight: Bold
  rate-color: Primary
  spread-color: Error (if > 3%), Success (if < 1%)
  total-label-size: 16/20
  total-value-size: 18/22
  total-value-weight: Bold
```

### FX Chart (Sparkline & Full)
```
FXChart:
  sparkline-height: 40
  sparkline-width: 120
  line-color: Primary
  line-width: 2
  gradient-from: Primary 30%
  gradient-to: Primary 0%
  full-chart-height: 200
  chart-grid-color: Neutral 10
  chart-label-size: 11
  chart-label-color: Neutral 50
  y-axis-label-font: JetBrains Mono
  x-axis-label-font: System
```

### Price Change Indicator
```
PriceChange:
  up-color: Success
  down-color: Error
  unchanged-color: Neutral 40
  arrow-size: 12
  percent-size: 12/16
  percent-weight: Medium
  absolute-change-size: 11/14
  absolute-change-weight: Regular
```

### Admin Rate Override
```
AdminOverride:
  badge-bg: Warning 20%
  badge-color: Warning (dark)
  badge-font-size: 11/14
  badge-weight: Medium
  input-background: Neutral 0
  input-font: JetBrains Mono
  input-size: 28
  input-weight: Bold
  reason-field-height: 80
  audit-log-size: 12/16
  audit-log-font: JetBrains Mono
```
