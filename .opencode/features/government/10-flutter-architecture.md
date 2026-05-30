# Government Collections Flutter Architecture

## Architecture Pattern
```
Feature-first modular architecture with Clean Architecture layers:

lib/
├── core/
│   ├── api/                           # Dio client, interceptors
│   ├── design/                        # Design tokens, shared widgets
│   ├── utils/                         # Formatters (currency, date, Arabic)
│   └── services/                      # Biometrics, notifications, deep links
│
├── features/
│   └── government/
│       ├── data/
│       │   ├── datasources/
│       │   │   ├── GovernmentRemoteDataSource.dart    # API calls
│       │   │   ├── GovernmentLocalDataSource.dart     # SQLite cache
│       │   │   └── GovernmentReceiptDataSource.dart   # PDF/QR generation
│       │   ├── models/
│       │   │   ├── TaxQueryModel.dart
│       │   │   ├── TaxPaymentModel.dart
│       │   │   ├── FineQueryModel.dart
│       │   │   ├── PassportPaymentModel.dart
│       │   │   ├── TuitionQueryModel.dart
│       │   │   ├── VehicleQueryModel.dart
│       │   │   ├── CourtFeeModel.dart
│       │   │   ├── MunicipalityFeeModel.dart
│       │   │   ├── GovernmentReceiptModel.dart
│       │   │   ├── GovernmentTransactionModel.dart
│       │   │   ├── GovernmentBillerModel.dart
│       │   │   └── SavedPayerModel.dart
│       │   └── repositories/
│       │       └── GovernmentRepositoryImpl.dart
│       │
│       ├── domain/
│       │   ├── entities/
│       │   │   ├── TaxObligation.dart
│       │   │   ├── FineObligation.dart
│       │   │   ├── TuitionObligation.dart
│       │   │   ├── PassportApplication.dart
│       │   │   ├── VehicleObligation.dart
│       │   │   ├── CourtFeeObligation.dart
│       │   │   ├── MunicipalityObligation.dart
│       │   │   ├── GovernmentReceipt.dart
│       │   │   ├── GovernmentTransaction.dart
│       │   │   ├── GovernmentBiller.dart
│       │   │   └── SavedPayer.dart
│       │   ├── repositories/
│       │   │   └── GovernmentRepository.dart          # Abstract interface
│       │   └── usecases/
│       │       ├── QueryTaxUseCase.dart
│       │       ├── PayTaxUseCase.dart
│       │       ├── QueryFineUseCase.dart
│       │       ├── PayFineUseCase.dart
│       │       ├── QueryTuitionUseCase.dart
│       │       ├── PayTuitionUseCase.dart
│       │       ├── QueryPassportUseCase.dart
│       │       ├── PayPassportUseCase.dart
│       │       ├── QueryVehicleUseCase.dart
│       │       ├── PayVehicleUseCase.dart
│       │       ├── QueryCourtFeeUseCase.dart
│       │       ├── PayCourtFeeUseCase.dart
│       │       ├── QueryMunicipalityUseCase.dart
│       │       ├── PayMunicipalityUseCase.dart
│       │       ├── GetPaymentHistoryUseCase.dart
│       │       ├── GenerateReceiptUseCase.dart
│       │       ├── VerifyReceiptUseCase.dart
│       │       └── SavePayerUseCase.dart
│       │
│       └── presentation/
│           ├── providers/
│           │   ├── GovernmentHubProvider.dart
│           │   ├── TaxPaymentProvider.dart
│           │   ├── FinePaymentProvider.dart
│           │   ├── PassportPaymentProvider.dart
│           │   ├── TuitionPaymentProvider.dart
│           │   ├── VehiclePaymentProvider.dart
│           │   ├── CourtPaymentProvider.dart
│           │   ├── MunicipalityPaymentProvider.dart
│           │   ├── PaymentHistoryProvider.dart
│           │   └── ReceiptProvider.dart
│           ├── screens/
│           │   ├── GovernmentHubScreen.dart
│           │   ├── TaxQueryScreen.dart
│           │   ├── TaxPaymentScreen.dart
│           │   ├── FineQueryScreen.dart
│           │   ├── PassportPaymentScreen.dart
│           │   ├── TuitionPaymentScreen.dart
│           │   ├── VehiclePaymentScreen.dart
│           │   ├── CourtFeeScreen.dart
│           │   ├── MunicipalityScreen.dart
│           │   ├── PaymentHistoryScreen.dart
│           │   ├── ReceiptScreen.dart
│           │   └── ReceiptDetailScreen.dart
│           └── widgets/
│               ├── ServiceGridCard.dart
│               ├── FeeBreakdownCard.dart
│               ├── GovernmentReceiptCard.dart
│               ├── MinistryBadge.dart
│               ├── SavedPayerChip.dart
│               ├── AmountDisplay.dart
│               ├── IdInputField.dart
│               └── DeadlineCountdown.dart
```

## Key Architectural Decisions
1. **Dynamic service registry** — Services loaded from API, not hardcoded
2. **Payment flow as state machine** — `idle → querying → displaying → confirming → processing → success/error`
3. **Offline receipt vault** — All receipts saved locally as PDF + QR data
4. **Saved payers** — Encrypted local storage for frequently-used IDs
5. **Deep linking** — `beza://government/tax/{taxId}` for direct ministry links
