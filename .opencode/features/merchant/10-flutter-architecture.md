# Merchant Flutter Architecture

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
│   ├── network/                   # Connectivity checker, cache
│   ├── router/                    # GoRouter configuration
│   ├── services/                  # Platform services (voice, camera)
│   ├── storage/                   # Local storage, secure storage
│   └── utils/                     # Formatters, validators, constants
│
├── features/
│   └── merchant/
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
  ┌──────────────────────────────────────────┐
  │        MerchantAuthProvider              │
  │  (merchant auth state, business info)    │
  └────────────┬─────────────────────────────┘
               │
  ┌────────────▼─────────────────────────────┐
  │      MerchantDashboardProvider           │
  │  (today's sales, transaction count,      │
  │   trend, settlement preview)             │
  └────────────┬─────────────────────────────┘
               │
  ┌────────────▼─────────────────────────────┐
  │       MerchantQrProvider                 │
  │  (QR code image, type, amount preset,   │
  │   scan count)                            │
  └────────────┬─────────────────────────────┘
               │
  ┌────────────▼─────────────────────────────┐
  │     PaymentLinkProvider                  │
  │  (create, share, list active/expired)    │
  └────────────┬─────────────────────────────┘
               │
  ┌────────────▼─────────────────────────────┐
  │   MerchantTransactionProvider            │
  │  (list, filter, search, paginate)        │
  └────────────┬─────────────────────────────┘
               │
  ┌────────────▼─────────────────────────────┐
  │    MerchantSettlementProvider            │
  │  (daily preview, history, download PDF)  │
  └──────────────────────────────────────────┘
```

## Data Flow (QR Payment Example)
```
Customer scans merchant QR → Beza Customer App
  → Customer enters amount → confirms with PIN
  → POST /api/v1/merchant/qr/pay {qr_id, amount, pin}
  → Backend: QRService.verify() → CfeService.hold() → process
  → WebSocket event: PaymentCompleted {merchant_id, amount, qr_id}
  → MerchantApp receives WebSocket event
  → MerchantDashboardProvider.addTransaction(txn)
  → Update today's sales total (animated counter)
  → Play sound + vibrate + voice: "تم استلام 45,000 ل.س"
  → Show in recent transactions list
```

## Offline Strategy
```
Merchant app offline capabilities:
  1. QR Code: Stored locally (never needs internet to display)
  2. Payment Links: Generated server-side; merchant needs internet to create
  3. Transaction History: Cached locally (last 200 transactions in SQLite)
  4. Sales Dashboard: Last known sales cached; shows banner "قديمة" (stale)
  5. Settlement Reports: Not available offline (download PDF when online)
  6. POS Terminal: Transaction queue → process when online (POS app)

Sync Triggers:
  - App foreground
  - Connectivity restored (ConnectivityPlus listener)
  - Pull-to-refresh on dashboard
  - Periodic background sync (15 min)

Cached Data:
  - Last known daily sales (encrypted SQLite)
  - Last 200 transactions (SQLite, full text search by phone)
  - QR codes (PNG stored in app cache directory)
  - Merchant profile (cached on login)
```

## Voice & Sound Integration
```dart
// Critical for low-literacy merchants
class MerchantVoiceService {
  void announcePayment(int amount, String currency) {
    TtsService.speak("تم استلام $amount $currency");
  }

  void announceError(String message) {
    TtsService.speak(message); // e.g. "فشلت عملية الدفع"
  }

  void playPaymentSound() {
    AudioPlayer.play('sounds/payment_received.mp3');
  }

  void playRegistrationComplete() {
    AudioPlayer.play('sounds/merchant_activated.mp3');
  }
}
```
