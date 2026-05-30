# Open Finance Design Tokens

## Portal-Specific Tokens
Inherits all global design tokens from Beza Design Language 2026.

### Stat Cards
```
StatCard:
  background: Neutral 0
  border-radius: 12
  padding: 20
  shadow: sm (0 1 3 rgba(0,0,0,0.08))
  value-size: 28/36
  value-weight: Bold
  value-color: Dark
  label-size: 14/18
  label-color: Neutral 60
  icon-color: Primary
  trend-up-color: Success
  trend-down-color: Error
```

### API Endpoint Block
```
EndpointBlock:
  method-badge-radius: 4
  method-badge-padding: 4 8
  method-badge-font-size: 12
  method-badge-weight: Bold
  method-colors:
    GET: Primary (bg: Primary 10%)
    POST: Success (bg: Success 10%)
    PUT: Warning (bg: Warning 10%)
    DELETE: Error (bg: Error 10%)
  path-color: Neutral 80
  path-font: JetBrains Mono
  path-size: 14
```

### Code Block
```
CodeBlock:
  background: Dark (Neutral 90)
  border-radius: 8
  padding: 16
  font-family: JetBrains Mono
  font-size: 13
  line-height: 1.6
  color: White
  keyword-color: #FF79C6
  string-color: #50FA7B
  comment-color: #6272A4
```

### Sidebar Navigation
```
SidebarNav:
  width: 260
  item-height: 44
  item-padding: 12 16
  item-radius: 8
  item-color: Neutral 70
  item-color-active: Primary
  item-bg-active: Primary 8%
  icon-size: 20
  section-header-size: 12
  section-header-color: Neutral 50
  section-header-weight: Bold
```

### Status Indicators
```
ServiceStatus:
  dot-size: 8
  dot-space-right: 8
  operational-color: Success
  degraded-color: Warning
  outage-color: Error
  label-size: 14/18
  latency-size: 12/16
  latency-color: Neutral 60
```

### API Console (Playground)
```
Playground:
  request-panel-bg: Neutral 0
  response-panel-bg: Neutral 95
  border-color: Neutral 20
  header-editor-font: JetBrains Mono 13
  body-editor-font: JetBrains Mono 13
  send-button-bg: Primary
  send-button-color: White
  send-button-radius: 8
  status-badge-radius: 4
  status-badge-200-color: Success
  status-badge-400-color: Warning
  status-badge-500-color: Error
```
