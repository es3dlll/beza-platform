# Government Collections Flutter Navigation

## Route Definitions (GoRouter)

```dart
class GovernmentRoutes {
  static const hub = '/government';
  static const tax = '/government/tax';
  static const taxPay = '/government/tax/pay';
  static const fine = '/government/fine';
  static const passport = '/government/passport';
  static const tuition = '/government/tuition';
  static const vehicle = '/government/vehicle';
  static const court = '/government/court';
  static const municipality = '/government/municipality';
  static const civilRegistry = '/government/civil-registry';
  static const history = '/government/history';
  static const receipt = '/government/receipt/:receiptId';
  static const receiptDetail = '/government/receipt/:receiptId/detail';
  static const savedPayers = '/government/saved-payers';
}

List<RouteBase> governmentRoutes = [
  GoRoute(
    path: '/government',
    name: 'government.hub',
    builder: (context, state) => const GovernmentHubScreen(),
    routes: [
      GoRoute(
        path: 'tax',
        name: 'government.tax',
        builder: (context, state) => const TaxQueryScreen(),
      ),
      GoRoute(
        path: 'tax/pay',
        name: 'government.taxPay',
        builder: (context, state) => TaxPaymentScreen(
          taxId: state.uri.queryParameters['taxId']!,
          obligations: state.extra as List<TaxObligation>,
        ),
      ),
      GoRoute(
        path: 'fine',
        name: 'government.fine',
        builder: (context, state) => const FineQueryScreen(),
      ),
      GoRoute(
        path: 'passport',
        name: 'government.passport',
        builder: (context, state) => const PassportPaymentScreen(),
      ),
      GoRoute(
        path: 'tuition',
        name: 'government.tuition',
        builder: (context, state) => const TuitionPaymentScreen(),
      ),
      GoRoute(
        path: 'vehicle',
        name: 'government.vehicle',
        builder: (context, state) => const VehiclePaymentScreen(),
      ),
      GoRoute(
        path: 'court',
        name: 'government.court',
        builder: (context, state) => const CourtFeeScreen(),
      ),
      GoRoute(
        path: 'municipality',
        name: 'government.municipality',
        builder: (context, state) => const MunicipalityScreen(),
      ),
      GoRoute(
        path: 'civil-registry',
        name: 'government.civilRegistry',
        builder: (context, state) => const CivilRegistryScreen(),
      ),
      GoRoute(
        path: 'history',
        name: 'government.history',
        builder: (context, state) => const PaymentHistoryScreen(),
      ),
      GoRoute(
        path: 'receipt/:receiptId',
        name: 'government.receipt',
        builder: (context, state) => ReceiptScreen(
          receiptId: state.pathParameters['receiptId']!,
        ),
        routes: [
          GoRoute(
            path: 'detail',
            name: 'government.receiptDetail',
            builder: (context, state) => ReceiptDetailScreen(
              receiptId: state.pathParameters['receiptId']!,
            ),
          ),
        ],
      ),
      GoRoute(
        path: 'saved-payers',
        name: 'government.savedPayers',
        builder: (context, state) => const SavedPayersScreen(),
      ),
    ],
  ),
];

// Deep linking support
// beza://government/tax?taxId=1234567890
// beza://government/tuition?studentId=2024123456
// beza://government/receipt/GOV-2025-7823
```
