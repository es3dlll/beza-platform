# FX Engine Flutter Architecture

## Architecture Pattern
```
Feature-first modular architecture with Clean Architecture layers:

lib/
├── core/                          # Shared across all features
│   ├── api/                       # Dio client, interceptors, retry
│   ├── auth/                      # Auth state, token management
│   ├── design/                    # BezaTheme, design tokens, widgets
│   ├── errors/                    # Failure types, error handling
│   ├── network/                   # Connectivity checker, retry
│   ├── router/                    # GoRouter configuration
│   ├── services/                  # Platform services
│   └── utils/                     # Formatters, validators
│
└── features/
    └── fx/
        ├── data/
        │   ├── datasources/       # Remote (API) + Local (SQLite)
        │   ├── models/            # JSON serializable models
        │   └── repositories/      # Repository implementations
        ├── domain/
        │   ├── entities/          # Pure Dart entities
        │   ├── repositories/      # Abstract repository interfaces
        │   └── usecases/          # Business logic use cases
        └── presentation/
            ├── providers/         # Riverpod providers
            ├── screens/           # Full screens
            ├── widgets/           # Reusable widgets
            └── state/             # State classes
```

## State Management (Riverpod)
```
Provider Hierarchy:
  ┌─────────────────────────────────────────┐
  │         AuthNotifierProvider            │
  │   (auth state: logged in, token, user)  │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │        FXRateListProvider               │
  │  (all live rates, pairs, last updated,  │
  │   loading/error, refresh() )            │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │     ConversionFormProvider              │
  │  (source wallet, target wallet, amount, │
  │   rate preview, lock state, validation) │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │     RateLockProvider                    │
  │  (lock status, timer, expiry)          │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │    ConversionExecutionProvider          │
  │  (loading, success/failure, receipt)    │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │    ConversionHistoryProvider            │
  │  (list, pagination, filters)           │
  └─────────────────────────────────────────┘
```

## Data Flow (Conversion Example)
```
User enters amount → ConversionFormProvider:
  → Fetches live rate via FXRateListProvider
  → Calculates output amount with spread
  → Shows preview with rate, fee, total
  → User taps "Lock Rate"
  → RateLockProvider: POST /fx/lock
      → Lock acquired: 30s timer starts
      → If lock fails: show error, retry option
  → User confirms with PIN
  → ConversionExecutionProvider: POST /fx/convert
      → API Gateway → Backend → CFE
      → Response: ConversionResult with receipt
  → Update wallet balances (optimistic)
  → Add to ConversionHistoryProvider
  → Navigate to SuccessScreen
  → On error: rollback optimistic update, show error
```

## Offline Strategy
```
Cached Data:
  - Last known rates (SQLite, TTL 30s in cache, 5min stale allowed)
  - Last 50 conversions (SQLite)
  - Provider metadata (SQLite, synced on login)

Behavior:
  - Offline: Show last known rates with "قد تكون قديمة" (May be stale)
  - Rate lock requires online connection (cannot lock offline)
  - Conversion history available offline (cached)
  - Pending conversions (rare): queue if network fails mid-conversion
```

## Rate Refresh Strategy
```dart
// Auto-refresh rates every 15 seconds while on Exchange screen
// Pause when app is backgrounded, resume on foreground
// Manual refresh via pull-to-refresh

class FXRateAutoRefresh {
  Timer? _timer;

  void start() {
    _timer = Timer.periodic(const Duration(seconds: 15), (_) {
      ref.read(fxRateListProvider.notifier).refresh();
    });
  }

  void stop() {
    _timer?.cancel();
  }
}
```
