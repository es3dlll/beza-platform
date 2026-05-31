# Remittance Flutter Architecture

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
│   └── remittance/
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
  ┌─────────────────────────────────────┐
  │  RemittanceFormProvider             │
  │  (recipient, amount, currency,      │
  │   note, validation state)           │
  └────────────┬────────────────────────┘
               │
  ┌────────────▼────────────────────────┐
  │  FXRateProvider                     │
  │  (live rates, locked rate, expiry)  │
  └────────────┬────────────────────────┘
               │
  ┌────────────▼────────────────────────┐
  │  TransferExecutionProvider          │
  │  (loading, success/failure, receipt)│
  └────────────┬────────────────────────┘
               │
  ┌────────────▼────────────────────────┐
  │  BeneficiaryProvider                │
  │  (saved list, add, edit, delete)    │
  └────────────┬────────────────────────┘
               │
  ┌────────────▼────────────────────────┐
  │  RecurringTransferProvider          │
  │  (list, create, pause, cancel)      │
  └────────────┬────────────────────────┘
               │
  ┌────────────▼────────────────────────┐
  │  RemittanceHistoryProvider          │
  │  (list, pagination, filter, search) │
  └─────────────────────────────────────┘
```

## Data Flow (Diaspora Remittance Example)
```
User taps "Send" → SendRemittanceScreen
  → RemittanceFormProvider validates:
      - Beneficiary selected ✓
      - Amount > 0 ✓
      - Amount <= daily limit ✓
      - FX rate locked or lock requested ✓
  → User confirms with biometric → _executeTransfer()
  → Call ExecuteRemittanceUseCase.execute()
  → RemittanceRepository.sendRemittance()
  → RemoteDataSource.postRemittance()
    → API Gateway → Backend → FX Engine → CFE → Compliance
  → Response: RemittanceResult
  → Update RemittanceHistoryProvider (add to list)
  → Navigate to SuccessScreen
  → On error: show error, do not debit
```

## Offline Strategy
```
Write-ahead log for transfers:
  1. When send is initiated, save PendingTransfer to local SQLite
  2. Display: "قيد الإرسال" (Sending) status
  3. When online, process queue (FIFO, with retry 3x)
  4. On success: update status to "Completed"
  5. On failure after 3 retries: notify user with "إعادة المحاولة" option

Sync Triggers:
  - App foreground
  - Connectivity restored (ConnectivityPlus listener)
  - Pull-to-refresh on transfer history
  - Periodic background sync (WorkManager, 15 min)

Cached Data:
  - Last 50 transfers (SQLite, full-text search)
  - Beneficiary list (SQLite, synced on login)
  - Corridor info (SQLite, synced daily)
  - FX rates (SQLite, cached 60s)
```

## Biometric Integration
```
Send Money Confirmation:
  1. Low amount (< $100 or 500,000 SYP): PIN only
  2. Medium ($100-$500): PIN + optional biometric
  3. High (> $500 or > 2,000,000 SYP): PIN + biometric REQUIRED
  4. Fraud-flagged transaction: step-up to biometric + SMS OTP

Diaspora-Specific:
  - First transfer to new beneficiary: biometric required
  - Recurring transfer setup: biometric required
  - Beneficiary details edit: biometric required
  - Daily limit increase request: biometric + OTP
```
