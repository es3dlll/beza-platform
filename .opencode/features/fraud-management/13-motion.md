# Motion & Animation — Fraud Management UX

## Philosophy

Fraud management motion should convey **urgency without panic**, **security without friction**. Every animation must have a purpose — inform, alert, guide, or reassure.

## Alert Surface Behavior

### Real-time Dashboard Auto-Refresh

```
┌─────────────────────────────────────────────────────────────┐
│  Dashboard auto-refresh behavior:                           │
│                                                             │
│  Normal: every 30 seconds                                    │
│  During P0 alert: every 5 seconds (auto-accelerate)         │
│  During spike: every 2 seconds (critical mode)              │
│  Manual: drag-to-refresh available at any time              │
│                                                             │
│  Visual: subtle pulse at top → "Auto-refreshing..."        │
│  No full page flash — only data panels update               │
│  Changed values animate from old → new (0.3s ease)         │
│                                                             │
│  When fraud rate changes:                                   │
│  • Up: value pulses red (2 pulses, 0.5s each)               │
│  • Down: value pulses green (1 pulse, 0.3s)                 │
│  • Steady: no animation                                      │
└─────────────────────────────────────────────────────────────┘
```

### P0 Alert — Critical Fraud Notification

```
┌─────────────────────────────────────────────────────────────┐
│  P0 ALERT ANIMATION (Ops Dashboard + Push Notification)     │
│                                                             │
│  ┌───┐                                                      │
│  │ 🔴│ 1. System detects P0-level fraud                     │
│  └───┘                                                      │
│    │                                                         │
│    ▼                                                         │
│  ┌─────────────────────────────┐                            │
│  │ ⚠️ P0 FRAUD ALERT          │ ← Alert panel slides in   │
│  │ ATO detected: User 8492    │   from right (0.4s ease)  │
│  │ Amount: 500,000 SYP         │                            │
│  │ ┌──────────┐ ┌──────────┐  │    Alert card:            │
│  │ │ View Now │ │ Snooze   │  │    • Red left border      │
│  │ └──────────┘ └──────────┘  │    • Pulsing dot (1s)     │
│  └─────────────────────────────┘    • Sound if enabled     │
│    │                                                         │
│    ▼                                                         │
│  ┌─────────────────────────────┐                            │
│  │ Push Notification (mobile): │                            │
│  │ 🔴 P0 FRAUD: User 8492     │ ← Critical alert banner   │
│  │ Account takeover in progress│   (no swipe to dismiss)   │
│  │ 500,000 SYP at risk         │                            │
│  └─────────────────────────────┘                            │
│                                                             │
│  Alert auto-redirects to Transaction Detail on tap          │
└─────────────────────────────────────────────────────────────┘
```

### P1/P2 Alert — Non-Critical Notifications

```
┌─────────────────────────────────────────────────────────────┐
│  P1/P2 ALERT BEHAVIOR                                       │
│                                                             │
│  P1 (High):                                                  │
│  • Dashboard: Orange left border card, slide-in (0.4s)      │
│  • Push: Standard notification, stackable                    │
│  • No sound (vibrate only on mobile)                         │
│  • Auto-dismiss from feed after 5 minutes if not reviewed   │
│                                                             │
│  P2 (Medium):                                               │
│  • Dashboard: Yellow left border card, fade-in (0.6s)       │
│  • Push: Silent notification (notification center only)     │
│  • No sound, no vibration                                    │
│  • Auto-dismiss after 30 minutes                             │
│                                                             │
│  Alert counter in sidebar updates with micro-animation      │
│  (number rolls up/down, 0.2s ease)                          │
└─────────────────────────────────────────────────────────────┘
```

### Critical Transaction Slowdown

```
┌─────────────────────────────────────────────────────────────┐
│  CRITICAL TRANSACTION SLOWDOWN                               │
│  (Artificial delay to allow real-time screening)            │
│                                                             │
│  For transactions scoring 50-79 (suspicious):               │
│  • User sees: processing animation (2-5 seconds)            │
│  • Backend: running verification checks                      │
│  • Visual:                                                 │
│    ┌────────────────────────────────────┐                   │
│    │ 🔄 Verifying transaction...        │                   │
│    │ ━━━━━━━━━━━━━━━░░░░░░░░░░░ 65%    │ ← Animated bar   │
│    │ Please wait a moment               │    2-5 seconds   │
│    └────────────────────────────────────┘                   │
│  • The delay is deliberate (250ms - 3s added)               │
│  • User cannot cancel during this phase                      │
│  • If verification passes → bar completes → success         │
│  • If verification fails → alert screen appears              │
│                                                             │
│  Rationale: The artificial delay makes the user pause       │
│  and think. For legitimate users, it's a minor wait.        │
│  For fraudsters, time pressure increases detection.         │
└─────────────────────────────────────────────────────────────┘
```

### Fraud Confirmation → Account Freeze

```
┌─────────────────────────────────────────────────────────────┐
│  ACCOUNT FREEZE ANIMATION                                    │
│                                                             │
│  1. Transaction Blocked Screen appears (slide up from       │
│     bottom, 0.3s ease-out)                                  │
│                                                             │
│  2. User taps "This wasn't me"                              │
│                                                             │
│  3. Transition:                                             │
│     ┌────────────────────────────────────┐                  │
│     │ 🔒 Freezing your account...        │  ← Shield icon  │
│     │                                    │     fills with  │
│     │  ████████████████████░░░░ 80%     │     red (1.5s)  │
│     │  We're protecting your money      │                  │
│     └────────────────────────────────────┘                  │
│                                                             │
│  4. Account Frozen screen:                                  │
│     ┌────────────────────────────────────┐                  │
│     │ 🔒 Account Frozen                  │  ← Slide from   │
│     │                                    │     left (0.5s) │
│     │ Your account is temporarily frozen │                  │
│     │ to prevent unauthorized access.    │                  │
│     │                                    │                  │
│     │ [Contact Support]  [See Details]   │                  │
│     └────────────────────────────────────┘                  │
│                                                             │
│  After freeze:                                              │
│  • App navigation disabled (user stays on freeze screen)    │
│  • Only "Contact Support" and "See Details" actions         │
│  • Background: subtle lock pattern animation                 │
└─────────────────────────────────────────────────────────────┘
```

### False Positive → Transaction Released

```
┌─────────────────────────────────────────────────────────────┐
│  FALSE POSITIVE RESOLUTION ANIMATION                         │
│                                                             │
│  When ops team marks as false positive OR user verifies:    │
│                                                             │
│  1. Current screen transitions:                             │
│     ┌────────────────────────────────────┐                  │
│     │ ✅ Transaction Approved            │  ← Green check  │
│     │                                    │     animation   │
│     │ 150,000 SYP sent to Mohammed A.   │     (0.5s)      │
│     │                                    │                  │
│     │ ┌──────────────────────────────┐   │  ← Card appears │
│     │ │ ✓ This was a false alarm.   │   │     with bounce  │
│     │ │ Your transaction is safe.   │   │     (0.3s)      │
│     │ └──────────────────────────────┘   │                  │
│     └────────────────────────────────────┘                  │
│                                                             │
│  2. Push notification (if app backgrounded):                │
│     ✅ Transaction TXN-28492 completed successfully         │
│                                                             │
│  3. SMS sent (for agent/USSD users):                        │
│     "Your transaction of 150,000 SYP has been completed.    │
│      We apologize for the delay."                           │
│                                                             │
|  4. User's fraud flag is REMOVED from their profile         │
│     (they appear as a regular user again)                   │
└─────────────────────────────────────────────────────────────┘
```

### Case Status Updates

```
┌─────────────────────────────────────────────────────────────┐
│  CASE STATUS TRANSITIONS (Operations Dashboard)             │
│                                                             │
│  When case progresses:                                      │
│  1. Case card animates — brief highlight flash              │
│  2. Status badge animates to new state (crossfade 0.3s)    │
│  3. Case moves up/down sorted list (animated list reorder) │
│  4. Color changes:                                          │
│     • Alert → Investigation: gray → blue                    │
│     • → Confirmed: blue → red                               │
│     • → False Positive: blue → green                        │
│     • → Closed: any → gray                                  │
│                                                             │
│  Timeline entries animate in chronologically:               │
│  Each new event fades in at the top of the timeline         │
│  (or bottom, depending on direction preference)             │
│  Timestamp is relative: "2 minutes ago" → "5 minutes ago"  │
│  (live-updating)                                            │
└─────────────────────────────────────────────────────────────┘
```

### Risk Score Visualization

```
┌─────────────────────────────────────────────────────────────┐
│  RISK SCORE METER                                           │
│                                                             │
│  Real-time animated gauge on transaction detail:            │
│                                                             │
│  ┌────────────────────────────────────────────────┐        │
│  │                   78                            │        │
│  │  ░░░░░░░░░░░░░░████████████████████░░░░░░░░   │        │
│  │  0              50              100             │        │
│  │  Safe           Suspicious      Blocked         │        │
│  │  (green)        (yellow)        (red)          │        │
│  └────────────────────────────────────────────────┘        │
│                                                             │
│  When score recalculates:                                  │
│  • Number rolls to new value (0.3s ease-out)               │
│  • Gauge fills/empties with smooth transition               │
│  • Color transitions smoothly (green → yellow → red)       │
│  • Individual factor scores pulse when they change          │
└─────────────────────────────────────────────────────────────┘
```

## Push Notification Strategy

| Alert Type | Channel | Sound | Priority | Dismissible |
|------------|---------|-------|----------|-------------|
| P0 (Critical fraud) | Push + SMS + Dashboard | Critical alert sound | Highest | No (must view) |
| P1 (High fraud) | Push + Dashboard | Default | High | Yes, after 5 min |
| P2 (Medium) | Dashboard only | None | Normal | Yes |
| FP resolution | Push + SMS | Success | Normal | Yes |
| Case update | Push | None | Normal | Yes |
| Weekly report | Dashboard notification | None | Low | Yes |

## Performance Considerations

| Animation | Target | Concern |
|-----------|--------|---------|
| Dashboard auto-refresh | No jank, smooth update | Large data sets may cause layout shift |
| Alert slide-in | < 400ms | Avoid layout shift on alert panel |
| Gauge transitions | 60fps | SVG/Canvas rendering on frequently updating graphs |
| Mobile push response | < 1s tap-to-open | Deep link to correct screen |
| SMS arrival | < 30s | External dependency (SMS gateway) |
