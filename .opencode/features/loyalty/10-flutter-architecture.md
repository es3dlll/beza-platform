# Loyalty Flutter Architecture

## Architecture Pattern
```
Feature-first modular architecture:

lib/
├── core/
│   ├── api/                    # Dio client
│   ├── auth/                   # Auth state
│   ├── design/                 # Design tokens, widgets
│   ├── errors/                 # Failure types
│   └── utils/                  # Formatters, validators
│
├── features/
│   └── loyalty/
│       ├── data/
│       │   ├── datasources/    # Remote (API) + Local (SQLite)
│       │   ├── models/         # JSON serializable
│       │   └── repositories/   # Repository implementations
│       ├── domain/
│       │   ├── entities/       # Points, Tier, Reward, Redemption
│       │   ├── repositories/   # Abstract repository interfaces
│       │   └── usecases/       # EarnPoints, RedeemPoints, CheckTier
│       └── presentation/
│           ├── providers/      # Riverpod providers
│           ├── screens/        # LoyaltyHub, TierProgress, Rewards, History
│           ├── widgets/        # PointsCard, TierCard, RewardItem
│           └── state/          # State classes
│
├── app.dart
└── main.dart
```

## State Management (Riverpod)
```
Provider Hierarchy:
  ┌─────────────────────────────────────────┐
  │         AuthNotifierProvider            │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │      LoyaltyPointsProvider              │
  │  (balance, earned today, history)       │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │         TierProvider                    │
  │  (current tier, progress, benefits)     │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │     RewardCatalogProvider               │
  │  (available rewards, categories)        │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │     RedemptionProvider                  │
  │  (redeem flow, confirmation, history)   │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │   MerchantCampaignProvider              │
  │  (active campaigns, my campaigns)       │
  └─────────────────────────────────────────┘
```

## Data Flow (Points Earning Example)
```
Transaction completed (e.g., send money 25,000 SYP)
  → Backend emits WalletDebited event
  → Loyalty Event Listener triggers PointsService
  → Calculate: 25,000 / 1,000 × 1.0 (tier multiplier) = 25 points
  → Insert points_ledger entry (DR: User Points Receivable, CR: Points Liability)
  → Update loyalty_points.balance
  → Update loyalty_member_tier_history if qualifies for upgrade
  → Emit PointsEarned event
  → Push notification to user
  → LoyaltyPointsProvider refetches balance
  → UI updates points counter with animation
```

## Offline Strategy
```
Points Balances:
  - Cached in SQLite (last known balance + last 50 transactions)
  - Synced on app foreground
  - Points displayed from cache immediately, refreshed async

Tier Info:
  - Cached with 1-hour TTL (tier changes only daily)
  - Stale data acceptable since tier recalculates daily

Reward Catalog:
  - Cached with 24-hour TTL
  - Fresh catalog loaded on app open if online

Redemption:
  - Requires online (real-time points balance check)
  - Offline: show "يتطلب اتصال بالإنترنت"
```
