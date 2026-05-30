# Motion System — Beza (بزة)

## Animation Principles

1. **Purposeful** — Every animation serves a function: feedback, guidance, or delight
2. **Performant** — 60fps target on mid-range devices (MediaTek Helio G series, Snapdragon 600+)
3. **RTL-aware** — Directional animations reverse automatically in RTL layout
4. **Reducible** — Respects `Reduce motion` OS setting; fall back to instant transitions

## Timing Reference

| Interaction | Duration | Curve | Notes |
|-------------|----------|-------|-------|
| Button press | 100ms | ease-out | Scale 0.97→1.0 |
| Toggle switch | 150ms | ease-in-out | |
| Checkbox/toggle | 150ms | spring | tension 200, friction 20 |
| Page push (LTR) | 250ms | ease-in-out | Slide left |
| Page push (RTL) | 250ms | ease-in-out | Slide right |
| Bottom sheet | 300ms | spring | tension 100, friction 10 |
| Modal overlay | 200ms | ease-out | Scale 0.9→1.0 + fade |
| Success checkmark | 400ms | spring | Scale 0→1.2→1.0 |
| Success display | 2,000ms | — | Auto-dismiss after |
| Error shake | 300ms | linear | 5px amplitude, 3 cycles |
| Loading skeleton | 1,500ms | linear | Shimmer cycle |
| Pull-to-refresh spinner | 800ms | linear | Per rotation |
| Three-dot bounce | 400ms | spring | Per cycle |

## Transition Specifications

### Page Push
- **LTR**: New screen slides in from right, current slides out to left (e-ink style)
- **RTL**: New screen slides in from left, current slides out to right
- **Overlay**: Both screens have subtle shadow (2dp elevation)
- **Duration**: 250ms

### Bottom Sheet
- **Entry**: Slide up from bottom edge with spring curve
- **Spring params**: tension 100, friction 10
- **Overlay background**: Black at 40% opacity, fades in over 200ms
- **Dismiss**: Slide down with same spring, 200ms
- **Sheet supports**: Swipe down to dismiss, drag handle indicator at top

### Fade Transitions
- **Content refresh**: Fade out (100ms) → new content → fade in (100ms)
- **Tab switching**: Cross-fade (200ms)
- **Error/empty state**: Fade in (200ms) after loading state

### Scale Transitions
- **Modal dialogs**: Scale 0.9→1.0 with fade, 200ms ease-out
- **Success checkmark**: Scale 0→1.2→1.0, spring curve, 400ms
- **Warning/error icons**: Scale with shake, 300ms

### Shared Element (Hero) Transitions
- Applied to: biller logo → bill detail, agent avatar → agent detail, transaction card → transaction detail
- Source element transforms position and scale to destination
- Duration: 250ms
- matchedGeometryEffect (SwiftUI) / Hero (Flutter) pattern

## Feedback Animations

### Button Press
1. Scale down to 0.97 over 100ms (ease-out)
2. Hold while pressed
3. On release: scale back to 1.0 over 80ms
4. Disabled buttons: no animation, opacity 0.4

### Success Feedback
- Green glow pulse: circular ripple from center, 200ms, spread radius 2x element size
- Checkmark draw: stroke animation (start→end) over 300ms
- Particle burst: 20 small circles, random direction, 600ms fade-out
- Soft haptic (HapticFeedbackType.heavy)

### Error Feedback
- Shake animation: X-axis oscillation
  - Amplitude: 5px
  - Cycles: 3
  - Duration: 300ms
- Red border flash on input fields (200ms)
- Error icon: attention triangle with pulse
- Haptic: HapticFeedbackType.error (iOS) / pattern vibration (Android)

### Loading Feedback
- **Three-dot bounce**: Dots scale 1.0→1.4→1.0, staggered 100ms each, 400ms cycle
- **Skeleton shimmer**: Linear gradient sweep left to right, 1,500ms cycle
- **Spinner**: Continuous rotation (Syrian flag colors: red → white → green → black segments)
- **Progress bar**: Determinate for bill fetch (0→100% over 1-3s), indeterminate for transfers

### Pull-to-Refresh
- Standard swipe-down gesture with threshold at 64dp
- While pulling: spinner rotates proportional to pull distance
- At threshold: spinner locks, refresh indicator shows
- Spinner uses Syrian flag colors: red, white, green, black in segments
- Release triggers refresh, spinner animates for 800ms min
- On complete: spinner fades out over 200ms

## Motion Tokens

| Token | Value | Usage |
|-------|-------|-------|
| motion-duration-micro | 100ms | Button press, haptic trigger |
| motion-duration-fast | 150ms | Toggle, checkbox, icon swap |
| motion-duration-normal | 200ms | Fade, scale, modal entry |
| motion-duration-medium | 250ms | Page transition, hero |
| motion-duration-slow | 300ms | Sheet, error shake |
| motion-duration-success | 400ms | Success animation |
| motion-duration-toast | 3,000ms | Toast display |
| motion-curve-spring | spring(100,10) | Sheet, success |
| motion-curve-ease-out | ease-out | Scale, button |
| motion-curve-ease-in-out | ease-in-out | Page transitions |
| motion-rtl-reverse | true | All directional animations |
