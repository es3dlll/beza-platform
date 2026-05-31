# Savings Design Tokens

## Color Tokens

### Goal-Specific Colors
```dart
class SavingsColors {
  // Progress bar gradient stops
  static const progressStart = Color(0xFFFF6B35);     // Red-orange (0%)
  static const progressMid = Color(0xFFFFD700);       // Yellow (50%)
  static const progressEnd = Color(0xFF2ECC71);       // Green (100%)

  // Status colors
  static const goalActive = Color(0xFF2ECC71);         // Green
  static const goalLocked = Color(0xFF3498DB);         // Blue
  static const goalCompleted = Color(0xFF27AE60);      // Dark green
  static const goalCancelled = Color(0xFFE74C3C);      // Red
  static const goalAtRisk = Color(0xFFE67E22);         // Orange

  // Round-up badge
  static const roundupBadge = Color(0xFF9B59B6);       // Purple

  // Profit
  static const profitPositive = Color(0xFF2ECC71);
  static const profitNegative = Color(0xFFE74C3C);

  // Team
  static const teamPrimary = Color(0xFF1ABC9C);        // Teal
  static const memberColor = Color(0xFF3498DB);        // Blue
}
```

## Typography
```dart
class SavingsTypography {
  // Amount display (goal detail main amount)
  static const amountDisplay = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 40,
    fontWeight: FontWeight.w700,
    height: 1.1,
  );

  // Goal name
  static const goalName = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 20,
    fontWeight: FontWeight.w600,
  );

  // Progress percentage
  static const progressPercent = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 32,
    fontWeight: FontWeight.w700,
  );

  // Milestone celebration
  static const celebration = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 24,
    fontWeight: FontWeight.w800,
  );

  // Arabic body
  static const bodyAr = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 14,
    fontWeight: FontWeight.w400,
    height: 1.5,
  );
}
```

## Spacing Tokens
```dart
class SavingsSpacing {
  static const goalCardPadding = EdgeInsets.all(16.0);
  static const goalCardMargin = EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0);
  static const amountSectionVertical = 24.0;
  static const progressBarHeight = 12.0;
  static const milestoneBadgeSize = 48.0;
  static const memberAvatarSize = 40.0;
  static const teamCardPadding = EdgeInsets.all(12.0);
}
```

## Icon Tokens
| Icon | Usage | Asset |
|------|-------|-------|
| Goal default | Default goal icon | `savings_goal_default` |
| Goal phone | Phone/electronics goal | `savings_goal_phone` |
| Goal travel | Travel goal | `savings_goal_travel` |
| Goal education | Education goal | `savings_goal_education` |
| Goal home | Home/furniture goal | `savings_goal_home` |
| Goal wedding | Wedding goal | `savings_goal_wedding` |
| Goal medical | Medical goal | `savings_goal_medical` |
| Round-up | Round-up feature icon | `savings_roundup` |
| Auto-save | Auto-save icon | `savings_autosave` |
| Team | Team goal icon | `savings_team` |
| Profit | Profit sharing icon | `savings_profit` |
| Lock | Goal lock icon | `savings_lock` |
| Milestone | Milestone celebration | `savings_milestone` |

## Animation Tokens
```dart
class SavingsAnimations {
  static const progressUpdate = Duration(milliseconds: 800);
  static const celebration = Duration(milliseconds: 1500);
  static const cardAppear = Duration(milliseconds: 300);
  static const roundupMicro = Duration(milliseconds: 1200);
  static const confettiDuration = Duration(seconds: 3);
  static const nudgeSlide = Duration(milliseconds: 400);
}
```
