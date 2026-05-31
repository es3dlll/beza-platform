# Bill Payment Flutter Architecture

## Architecture Pattern
```
Feature-first modular architecture with Clean Architecture layers:

lib/
├── core/                          # Shared across all features
│   ├── api/                       # Dio client, interceptors, retry
│   ├── auth/                      # Auth state, token management
│   ├── design/                    # BezaTheme, design tokens, widgets
│   ├── errors/                    # Failure types, error handling
│   ├── router/                    # GoRouter configuration
│   ├── storage/                   # Local storage, secure storage
│   └── utils/                     # Formatters, validators, constants
│
├── features/
│   └── bill_payment/
│       ├── data/
│       │   ├── datasources/
│       │   │   ├── BillRemoteDataSource.dart    # API calls
│       │   │   └── BillLocalDataSource.dart     # SQLite cache
│       │   ├── models/
│       │   │   ├── BillerModel.dart
│       │   │   ├── BillModel.dart
│       │   │   ├── BillTransactionModel.dart
│       │   │   ├── ScheduledBillModel.dart
│       │   │   └── BillReceiptModel.dart
│       │   └── repositories/
│       │       └── BillRepositoryImpl.dart
│       ├── domain/
│       │   ├── entities/
│       │   │   ├── Biller.dart
│       │   │   ├── Bill.dart
│       │   │   ├── BillTransaction.dart
│       │   │   ├── ScheduledBill.dart
│       │   │   └── BillReceipt.dart
│       │   ├── repositories/
│       │   │   └── BillRepository.dart (abstract)
│       │   └── usecases/
│       │       ├── FetchBillUseCase.dart
│       │       ├── PayBillUseCase.dart
│       │       ├── GetBillHistoryUseCase.dart
│       │       ├── ScheduleBillUseCase.dart
│       │       ├── GetScheduledBillsUseCase.dart
│       │       └── CancelScheduleUseCase.dart
│       └── presentation/
│           ├── providers/
│           │   ├── BillCategoryProvider.dart
│           │   ├── BillFetchProvider.dart
│           │   ├── BillPaymentProvider.dart
│           │   ├── BillHistoryProvider.dart
│           │   └── BillScheduleProvider.dart
│           ├── screens/
│           │   ├── BillCategoryScreen.dart
│           │   ├── CustomerIdEntryScreen.dart
│           │   ├── BillDetailScreen.dart
│           │   ├── PaymentResultScreen.dart
│           │   ├── BillHistoryScreen.dart
│           │   └── ScheduledBillsScreen.dart
│           ├── widgets/
│           │   ├── BillCategoryCard.dart
│           │   ├── BillerListItem.dart
│           │   ├── CustomerIdInput.dart
│           │   ├── BillDetailCard.dart
│           │   ├── LateFeeBanner.dart
│           │   ├── PaymentConfirmationSheet.dart
│           │   ├── PaymentSuccessAnimation.dart
│           │   ├── ReceiptCard.dart
│           │   ├── ScheduledBillTile.dart
│           │   ├── ReminderSettingsCard.dart
│           │   └── BillFilterTabBar.dart
│           └── state/
│               ├── BillFetchState.dart
│               ├── BillPaymentState.dart
│               └── BillListState.dart
│
├── app.dart                       # MaterialApp.router setup
└── main.dart                      # Entry point, providers setup
```

## State Management (Riverpod)

```
Provider Hierarchy:
  ┌─────────────────────────────────────────────┐
  │         AuthNotifierProvider                │
  │   (auth state: logged in, token, user)      │
  └────────────┬────────────────────────────────┘
               │
  ┌────────────▼────────────────────────────────┐
  │         BillCategoryProvider                │
  │  (biller list, categories, loading state)   │
  └────────────┬────────────────────────────────┘
               │
  ┌────────────▼────────────────────────────────┐
  │         BillFetchProvider                   │
  │  (customer ID, fetched bill, loading state) │
  └────────────┬────────────────────────────────┘
               │
  ┌────────────▼────────────────────────────────┐
  │        BillPaymentProvider                  │
  │  (payment status, receipt, loading state)   │
  └────────────┬────────────────────────────────┘
               │
  ┌────────────▼────────────────────────────────┐
  │        BillHistoryProvider                  │
  │  (list, pagination, filter by biller/date)  │
  └────────────┬────────────────────────────────┘
               │
  ┌────────────▼────────────────────────────────┐
  │       BillScheduleProvider                  │
  │  (scheduled bills, reminders, auto-pay)     │
  └─────────────────────────────────────────────┘
```

## Data Flow (Bill Pay Example)
```
User enters customer ID → CustomerIdEntryScreen
  → BillFetchProvider.fetchBill(billerType, customerId)
  → FetchBillUseCase.execute()
  → BillRepository.fetchBill()
  → BillRemoteDataSource.fetchBill(customerId, billerType)
  → API Gateway → Backend → BillerProviderService → Biller API
  → Response: Bill with amount, due date, breakdown
  → Store fetched bill in BillFetchProvider
  → Navigate to BillDetailScreen

User confirms payment with PIN → BillDetailScreen
  → BillPaymentProvider.payBill(billId, pin)
  → PayBillUseCase.execute()
  → BillRepository.payBill()
  → BillRemoteDataSource.postPayment()
  → API Gateway → Backend → BillPaymentService → Biller API + CFE
  → Response: PaymentResult with receipt
  → Navigate to PaymentResultScreen
  → Add to BillHistoryProvider (optimistic)
```

## Offline Strategy
```
Bill Payment Offline:
  1. Biller catalog cached in SQLite (synced daily)
  2. Recent customer IDs cached per biller (last 10)
  3. Payment queue: if biller API unavailable:
     - Save payment request local
     - Show "سيتم الدفع عند توفر الخدمة" (Will pay when online)
     - Process queue when online (FIFO, retry 3x)
     - Notify user on success/failure
  4. Receipts cached locally for 90 days
  5. Scheduled bills stored locally + synced to server
```
