# Wallet Flutter Architecture

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
│   └── wallet/
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
  │        WalletBalanceProvider            │
  │  (balance SYP, USD, last updated,       │
  │   loading/error state, refresh() )      │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │     TransactionListProvider             │
  │  (list, pagination, filter, search)     │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │     TransferFormProvider                │
  │  (recipient, amount, note, validation)  │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │      TransferExecutionProvider          │
  │  (loading, success/failure, receipt)    │
  └─────────────────────────────────────────┘
```

## Data Flow (Transfer Example)
```
User taps "Send" → TransferFormScreen
  → TransferFormProvider validates:
      - Phone number format ✓
      - Amount > 0 ✓
      - Amount <= balance ✓
      - Daily limit ✓
  → User confirms with PIN → _executeTransfer()
  → Call TransferUseCase.execute()
  → WalletRepository.sendMoney()
  → WalletRemoteDataSource.postTransaction()
  → API Gateway → Backend → CFE
  → Response: TransferResult
  → Update WalletBalanceProvider (optimistic: debit instantly)
  → Add to TransactionListProvider (optimistic)
  → Navigate to SuccessScreen
  → On error: rollback optimistic update, show error
```

## Offline Strategy
```
Write-ahead log for transfers:
  1. When send is initiated, save PendingTransaction to local SQLite
  2. Display: "قيد الإرسال" (Pending) status
  3. When online, process queue (FIFO, with retry 3x)
  4. On success: update status to "Completed"
  5. On failure after 3 retries: notify user with "إعادة المحاولة" option

Sync Triggers:
  - App foreground
  - Connectivity restored (ConnectivityPlus listener)
  - Pull-to-refresh on transaction history
  - Periodic background sync (WorkManager, 15 min)

Cached Data:
  - Last known balance (encrypted SQLite)
  - Last 50 transactions (SQLite, full text search)
  - Contact list (SQLite, synced on login)
  - Biller catalog (SQLite, synced daily)
```

## Biometric Integration
```
Send Money Confirmation:
  1. System checks: biometrics_enrolled? → device_supports?
  2. If yes: show Face ID / Fingerprint prompt
  3. On success: proceed with transfer
  4. On failure: fallback to PIN
  5. High-value (>500K SYP): requires BOTH biometric + PIN

Transaction Authorization:
  - Low amount (<50K SYP): PIN only
  - Medium (50K-500K SYP): PIN + optional biometric
  - High (>500K SYP): PIN + biometric required
  - Suspicious (fraud flag): step-up to biometric + SMS OTP
```
