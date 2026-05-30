# Government Collections Flutter Screens

## Screen Inventory

| # | Screen | Route | Widget |
|---|--------|-------|--------|
| 1 | Government Hub | `/government` | `GovernmentHubScreen` |
| 2 | Tax Query | `/government/tax` | `TaxQueryScreen` |
| 3 | Tax Payment | `/government/tax/pay` | `TaxPaymentScreen` |
| 4 | Fine Query | `/government/fine` | `FineQueryScreen` |
| 5 | Passport Payment | `/government/passport` | `PassportPaymentScreen` |
| 6 | Tuition Payment | `/government/tuition` | `TuitionPaymentScreen` |
| 7 | Vehicle Payment | `/government/vehicle` | `VehiclePaymentScreen` |
| 8 | Court Fee | `/government/court` | `CourtFeeScreen` |
| 9 | Municipality Fee | `/government/municipality` | `MunicipalityScreen` |
| 10 | Payment History | `/government/history` | `PaymentHistoryScreen` |
| 11 | Receipt | `/government/receipt/{id}` | `ReceiptScreen` |
| 12 | Receipt Detail | `/government/receipt/{id}/detail` | `ReceiptDetailScreen` |

## Key Screen Specifications

### GovernmentHubScreen
```dart
class GovernmentHubScreen extends ConsumerStatefulWidget {
  // Dependencies:
  // - GovernmentHubProvider (services, recentPayments, reminders)
  // - SavedPayerProvider (saved payer list)
}
// Behaviours:
// - Search filters service grid in real-time
// - ServiceGridCard taps → navigate to specific service screen
// - RecentPaymentCard taps → navigate to ReceiptScreen
// - ReminderCard taps → navigate to specific service with pre-filled ID
// - Pull-to-refresh reloads services and due amounts
```

### TaxQueryScreen
```dart
class TaxQueryScreen extends ConsumerStatefulWidget {
  // Uses TaxPaymentProvider
  // State machine: idle → querying → result → paying → receipt
}
// Behaviours:
// - Tax ID input with numeric keyboard
// - "حفظ الرقم" checkbox to save payer
// - "استعلام" button triggers POST /government/tax/query
// - On success: navigate to TaxPaymentScreen with obligations
// - Saved payers shown as chips for one-tap selection
```

### ReceiptScreen
```dart
class ReceiptScreen extends ConsumerStatefulWidget {
  final String receiptId;
  // Uses ReceiptProvider(receiptId)
}
// Behaviours:
// - Full-screen receipt with QR code (rendered via qr_flutter)
// - "مشاركة" → share PDF via platform share sheet
// - "تحميل PDF" → save to device
// - "إضافة إلى Apple Wallet" (if iOS) or "إضافة إلى Google Wallet"
// - Verify receipt via QR scanner
// - Receipt data cached offline
```
