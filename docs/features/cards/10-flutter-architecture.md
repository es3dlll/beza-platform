# Cards Flutter Architecture

## Architecture Pattern
```
Feature-first modular architecture with Clean Architecture layers:

lib/
├── core/                          # Shared across all features
│   ├── api/                       # Dio client, interceptors, retry
│   ├── auth/                      # Auth state, token management
│   ├── design/                    # BezaTheme, design tokens, widgets
│   ├── errors/                    # Failure types, error handling
│   ├── extensions/                # Dart extensions
│   ├── network/                   # Connectivity checker, retry
│   ├── router/                    # GoRouter configuration
│   ├── services/                  # Platform services (biometrics, etc.)
│   ├── storage/                   # Local storage, secure storage
│   └── utils/                     # Formatters, validators, constants
│
├── features/
│   └── cards/
│       ├── data/
│       │   ├── datasources/       # Remote (API) + Local (SQLite)
│       │   ├── models/            # JSON serializable models
│       │   └── repositories/      # Repository implementations
│       ├── domain/
│       │   ├── entities/          # Pure Dart entities
│       │   ├── repositories/      # Abstract repository interfaces
│       │   └── usecases/          # Business logic use cases
│       └── presentation/
│           ├── providers/         # Riverpod providers
│           ├── screens/           # Full screens
│           ├── widgets/           # Reusable widgets
│           └── state/             # State classes
│
├── app.dart                       # MaterialApp.router setup
└── main.dart                      # Entry point, providers setup
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
  │     CardListProvider                    │
  │  (list of user cards, loading/error,    │
  │   refresh, create, freeze/unfreeze)     │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │    CardDetailProvider                   │
  │  (single card state, limits,           │
  │   transactions paginated, PIN mgmt)     │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │  OneTimeCardProvider                    │
  │  (generate, amount, auto-destroy)       │
  └─────────────────────────────────────────┘
```

## Offline-First Strategy
- SQLite cache: card list, last 50 transactions per card
- Card actions (freeze, change PIN) require network (security)
- Display cached card data when offline with "بيانات مخزنة" banner
- One-time card generation requires network (HSM interaction)
- Transaction list: paginated with local cursor
