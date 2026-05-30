# Beza Design Language 2026

## Brand DNA
| Attribute | Expression |
|-----------|------------|
| **Trust** | Clean typography, clear hierarchy, transparent fees |
| **Speed** | Skeleton loading, instant transitions, optimistic UI |
| **Simplicity** | One primary action per screen, progressive disclosure |
| **Accessibility** | 44pt touch targets, WCAG AA+, RTL-native |
| **Modernity** | Glassmorphism, micro-interactions, fluid animations |
| **Syrian Identity** | Warm color palette, Arabic calligraphy accents |

## Color System
```
Primary:    #0D7C4A (Deep Green)    — Trust, Growth, Islamic Finance
Secondary:  #C8962E (Damascus Gold) — Value, Premium, Heritage
Accent:     #E8613A (Warm Orange)   — Action, Energy, Urgency
Success:    #22A67E (Emerald)       — Completed, Confirmed
Warning:    #F5A623 (Amber)         — Pending, Attention
Error:      #D32F2F (Crimson)       — Failed, Blocked
Info:       #2C6BED (Royal Blue)   — Information
Neutral 100:#1A1A1A (Dark)
Neutral 90: #2C2C2C
Neutral 80: #404040
Neutral 60: #6B6B6B
Neutral 40: #9E9E9E
Neutral 20: #D4D4D4
Neutral 10: #EDEDED
Neutral 0:  #F7F7F7
White:      #FFFFFF
```

## Typography
```
Primary:    Noto Sans Arabic (system)
            Weights: 300 (Light), 400 (Regular), 500 (Medium), 700 (Bold)

Monospace:  JetBrains Mono
            Usage: Amounts, codes, transaction references

Scale:
  Display:   32/36 — Hero headlines
  Title 1:   24/28 — Screen titles
  Title 2:   20/24 — Section headers
  Body:      16/22 — Primary content
  Body Small:14/20 — Secondary content
  Caption:   12/16 — Labels, timestamps
  Micro:     10/14 — Legal, footnotes
```

## Component Architecture

### Button System
```
Primary Button   → bg=Primary, text=White, h=52, radius=12
Secondary Button → bg=Neutral 10, text=Dark, h=52, radius=12
Ghost Button     → bg=transparent, text=Primary, h=52
Danger Button    → bg=Error, text=White, h=52, radius=12
FAB              → w=56, h=56, radius=28, bg=Primary, icon=White
Chip             → h=36, radius=18, bg=Primary 10% opacity
States: default, pressed (scale 0.97), loading (spinner), disabled (opacity 0.4)
```

### Input System
```
Text Input       → h=52, radius=12, border=1.5, label floating
Amount Input     → h=64, currency prefix, large font
Phone Input      → Country code prefix, mask +963 XXX XXX XXX
PIN Input        → 6 dots, secure, auto-submit
OTP Input        → 6 boxes, auto-advance, paste support
Search Input     → Icon prefix, clear button, debounced 300ms
States: default, focused (border=Primary), error (border=Error), success (checkmark), disabled
```

### Card System
```
Elevation Card   → shadow=0 2 12 rgba(0,0,0,0.08), radius=16
Border Card      → border=1 Neutral 20, radius=16, no shadow
Selection Card   → elevated + primary border when selected
Skeleton Card    → shimmer animation, 1.5s cycle
Goal Card        → progress ring, target + current amounts
Transaction Card → merchant icon + amount + status badge + timestamp
```

### Navigation
```
Bottom Tab Bar   → 5 tabs max, active icon + label, badge count
Top Tab Bar      → scrollable, underline active, 4-8 tabs
Navigation Rail  → (Tablet) icons + labels in column
Slide Drawer     → User profile, menu items, logout, width=80% screen
USSD Menu        → Numbered list, *123# → 1.Send 2.Pay 3.Agent 4.Bills 5.Savings
```

## Motion System
```
Duration:  200ms (micro), 300ms (standard), 400ms (emphasis)
           600ms (page transition)

Easing:
  easeOutCubic   → Elements entering screen
  easeInOutCubic → Page transitions
  spring(60,6)   → Interactive elements

Page Transition:
  iOS: Slide right (push), slide left (pop)
  Android: Fade through (material)

Micro-interactions:
  Button press    → scale 0.97, 50ms
  Card tap        → elevation increase, 100ms
  Amount change   → count up/down animation, 300ms
  Success         → green checkmark draw animation, 400ms
  Error           → shake animation, 300ms
  Pull to refresh → custom spinner, 800ms
```

## Screen Architecture
Every screen must define:
```
Screen Name:
Business Goal:
Psychological Goal:
Trust Signal:
Layout Structure:
  |-- Header (title, back, action)
  |-- Content (primary content area)
  |-- Footer (primary CTA)
  |-- Bottom Sheet (optional, for confirmations)
States:
  Loading: Skeleton shimmer (matching content layout)
  Empty: Illustration + message + CTA
  Error: Icon + message + retry button
  Offline: Banner + cached data
  Slow: Spinner after 3s timeout
  Success: Confetti (celebratory) / Checkmark (transactional)
Edge Cases:
  - What happens with zero balance?
  - What happens with network timeout?
  - What happens with expired session?
  - What happens with concurrent access?
  - What happens with partial completion?
```
