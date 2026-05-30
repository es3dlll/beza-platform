# Motion Guide

> Single source of truth for animation durations, easing curves, and transitions across ALL Beza Platform experiences.

## Duration Scale

| Token | Value | Use Cases |
|-------|-------|-----------|
| `duration-micro` | 50ms | Button press, ripple, toggle switch, checkmark draw |
| `duration-fast` | 100ms | Hover state, focus ring, tooltip show/hide |
| `duration-normal` | 200ms | Input focus, card tap feedback, badge appear |
| `duration-slow` | 300ms | Bottom sheet open, modal enter, page transitions |
| `duration-page` | 400ms | Page transition crossfade |
| `duration-extra` | 600ms | Success animation, confetti, celebration |

### Duration Decision Tree
```
User action
→ < 100ms: Instant feedback (button press, ripple) → 50ms
→ < 500ms: Micro-interaction (toggle, badge) → 100-200ms
→ < 1s: UI appear/disappear (sheet, modal) → 200-300ms
→ > 1s: Page transition → 400ms
→ Celebration/animation → 600ms
```

## Easing Curves

### Standard Easing
| Token | Curve | Usage |
|-------|-------|-------|
| `ease-emphasized` | `cubic-bezier(0.2, 0, 0, 1)` | Page transitions, sheet open/close, modal enter |
| `ease-emphasized-decelerate` | `cubic-bezier(0.05, 0.7, 0.1, 1)` | Element entering screen (drawer, sheet from bottom) |
| `ease-emphasized-accelerate` | `cubic-bezier(0.3, 0, 0.8, 0.15)` | Element leaving screen (sheet dismiss, modal close) |
| `ease-standard` | `cubic-bezier(0.4, 0, 0.2, 1)` | Hover, focus, general micro-interactions |
| `ease-decelerate` | `cubic-bezier(0, 0, 0.2, 1)` | Element appearing (fade in, scale in) |
| `ease-accelerate` | `cubic-bezier(0.4, 0, 1, 1)` | Element disappearing (fade out, scale out) |

### When to Use Which
```
Entering screen:  ease-emphasized-decelerate (smooth arrival, slow finish)
Leaving screen:   ease-emphasized-accelerate (fast start, ease out)
Tap feedback:     ease-standard (50ms)
Page transition:  ease-emphasized (400ms)
Hover:            ease-standard (100ms)
```

## Page Transitions

### iOS (Slide)
```
New screen slides from right → left
Duration: 400ms
Easing: ease-emphasized-decelerate (enter), ease-emphasized-accelerate (exit)

Navigation Bar: Title crossfade independently (200ms)
Content: Slides with page, has parallax (0.8x speed relative to nav)
Back gesture: Interactive, follows finger position, commits at 30% screen width
```

### Android (Fade + Elevation)
```
New screen fades in with slight elevation rise
Duration: 300ms
Easing: ease-emphasized-decelerate

Old screen: Fade out + scale 1.0 → 0.95 (opacity 1 → 0)
New screen: Fade in + scale 0.95 → 1.0 (opacity 0 → 1)
Back: Reverse animation, same duration
```

### Shared Element Transitions
- Elements with the same `transitionId` animate smoothly between screens
- Duration: 300ms
- Easing: ease-emphasized
- Properties: position, size, borderRadius, backgroundColor
- Example: Card expands to full detail screen

## Micro-Interactions

### Button Press
```
1. User touches button
2. Immediate (50ms): Scale 0.97, slightly darker background (brightness 0.9)
3. On release: Scale 1.0, original background
4. Duration: 50ms micro + 100ms recovery = 150ms total

State machine:
idle → pressed (50ms scale 0.97) → released (100ms scale 1.0) → idle
```

### Success Checkmark Draw
```
1. Circle draw: 300ms, ease-emphasized-decelerate, stroke-dashoffset animation
2. Checkmark draw: 100ms, ease-standard, delayed after circle completes
3. Total: 400ms

Stroke-dasharray technique:
- Circle: stroke-dasharray="100", stroke-dashoffset="100" → "0"
- Checkmark: stroke-dasharray="40", stroke-dashoffset="40" → "0" (after 300ms delay)
```

### Toggle Switch
```
Track: Background color transition (200ms, ease-standard)
Thumb: Translate X slide (200ms, ease-emphasized-decelerate)
Duration: 200ms total
```

### Input Focus
```
Border color: #C7C7CC → #1B5E20 (200ms, ease-standard)
Box shadow: 0 0 0 3px rgba(27, 94, 32, 0.15) (appear at 100ms)
Label float (if placeholder): Translate up 12px, scale 0.85 (200ms)
```

### Card Tap
```
Scale: 1.0 → 0.98 (100ms, ease-standard)
Elevation: shadow increases slightly
On release: reverse (100ms)
```

### Error Shake
```
Trigger: Form validation error
Animation: Translate X -4px, 4px, -4px, 4px, 0
Duration: 400ms
Easing: ease-standard
Only on first error appearance, not on re-type
```

### Pull to Refresh
```
1. Pull: Elastic resistance, max overshoot 80px
2. Threshold: 60px triggers refresh
3. Release: Springs back to refresh position (200ms)
4. Loading: Spinner rotates (800ms per revolution, linear)
5. Complete: Spinner fades out, brief success indicator (200ms)
```

### Skeleton Loader
```
Shimmer animation:
1. Linear gradient sweep left → right
2. Duration: 1500ms per cycle
3. Infinite loop (animation-iteration-count: infinite)
4. Base: #F2F2F7, Shimmer: #E5E5EA → #F2F2F7 → #E5E5EA

Keyframes:
0%   { background-position: -200% 0 }
100% { background-position: 200% 0 }
```

## Gesture Animations

### Swipe to Dismiss (iOS)
```
1. Finger drags card left or right
2. Card follows finger with 0.8x dampening
3. Background actions revealed (delete red, archive gray)
4. Past 40% threshold: auto-dismiss with spring animation
5. Below threshold: spring back to original position (200ms)
6. Spring: stiffness 300, damping 30
```

### Bottom Sheet Drag
```
1. Sheet follows finger Y position
2. Rubber-band effect at top: 0.5x resistance after max snap point
3. Drag down past 40%: dismiss with ease-emphasized-accelerate (200ms)
4. Below 40%: snap to nearest snap point (200ms, ease-emphasized)
5. Overlay: opacity changes based on drag position, 1.0 → 0.6
```

## Loading Sequences

### Initial App Load
```
Step 1: Splash screen (brand logo + loading spinner) — 0ms → visible
Step 2: Load core data (user, wallets) — spinner visible
Step 3: Core data loaded → crossfade splash → home screen (300ms)
Step 4: Home screen renders → skeleton for dynamic content
Step 5: Dynamic content loaded → skeleton → content (200ms stagger)
```

### Pull to Refresh Sequence
```
1. Pull down indicator: elastic
2. Release: spring to loading position
3. Loading: spinner animation (800ms loop)
4. Complete: spinner → checkmark (400ms), then hide
5. Content fades in if refreshed (200ms)
```

## Performance Requirements
- All animations run at 60fps (16.67ms per frame)
- Use `will-change: transform, opacity` for animated elements
- Avoid animating `width`, `height`, `top`, `left` (use transforms)
- GPU-accelerated properties only: `transform`, `opacity`
- Animation library: Framer Motion (web), Reanimated (mobile)
- Reduce motion accessibility: `prefers-reduced-motion: reduce` → disable all non-essential animations
