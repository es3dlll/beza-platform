# Bill Payment Flutter Navigation

## Route Configuration (GoRouter)

```dart
final router = GoRouter(
  initialLocation: '/wallet',
  routes: [
    ShellRoute(
      builder: (context, state, child) => AppShell(child: child),
      routes: [
        // ... other feature routes

        // Bill Payment Routes
        GoRoute(
          path: '/bills',
          name: 'billCategories',
          builder: (context, state) => const BillCategoryScreen(),
          routes: [
            GoRoute(
              path: 'enter-id/:billerType',
              name: 'customerIdEntry',
              builder: (context, state) => CustomerIdEntryScreen(
                billerType: state.pathParameters['billerType']!,
              ),
            ),
            GoRoute(
              path: 'detail/:billId',
              name: 'billDetail',
              builder: (context, state) => BillDetailScreen(
                billId: state.pathParameters['billId']!,
              ),
            ),
            GoRoute(
              path: 'result/:status',
              name: 'paymentResult',
              builder: (context, state) => PaymentResultScreen(
                status: state.pathParameters['status']!,
                extra: state.extra as Map<String, dynamic>?,
              ),
            ),
            GoRoute(
              path: 'history',
              name: 'billHistory',
              builder: (context, state) => const BillHistoryScreen(),
              routes: [
                GoRoute(
                  path: ':id',
                  name: 'billTransactionDetail',
                  builder: (context, state) => BillTransactionDetailScreen(
                    transactionId: state.pathParameters['id']!,
                  ),
                ),
              ],
            ),
            GoRoute(
              path: 'scheduled',
              name: 'scheduledBills',
              builder: (context, state) => const ScheduledBillsScreen(),
              routes: [
                GoRoute(
                  path: 'new',
                  name: 'scheduleNew',
                  builder: (context, state) => const ScheduleNewScreen(),
                ),
              ],
            ),
          ],
        ),
      ],
    ),
  ],
);
```

## Navigation Flow Map
```
BillCategoryScreen (/bills)
  ├── Tap category → show biller bottom sheet
  │   └── Select biller → /bills/enter-id/:billerType
  │       └── Enter ID + tap "استعلام"
  │           └── Bill fetched → /bills/detail/:billId
  │               └── Confirm payment → PaymentConfirmationSheet (modal)
  │                   └── Success → /bills/result/success
  │                   └── Failure → /bills/result/failure
  │
  ├── Tap Recent Bill → /bills/enter-id/:billerType (pre-filled)
  │
  ├── Tap "السجل" (bottom tab or header) → /bills/history
  │   └── Tap transaction → /bills/history/:id
  │
  └── Tap "المجدولة" (header link) → /bills/scheduled
      └── Tap "إضافة" → /bills/scheduled/new
          └── Create → /bills/scheduled

PaymentConfirmationSheet (Bottom Sheet — not a route):
  - Presented as showModalBottomSheet from BillDetailScreen
  - On confirm → execute payment → navigate to result
  - Independent of route stack
```

## Deep Links
```dart
// Incoming deep links handled by GoRouter redirect
// beza-app://bills
// beza-app://bills/electricity
// beza-app://bills/pay/billerType=peed&customerId=123456789012345678901234
// beza-app://bills/receipt/BILL-PEED-202606-XXXXX

final router = GoRouter(
  redirect: (context, state) {
    final uri = Uri.parse(state.uri.toString());
    if (uri.scheme == 'beza-app') {
      return _handleBillDeepLink(uri);
    }
    return null;
  },
);

String? _handleBillDeepLink(Uri uri) {
  switch (uri.host) {
    case 'bills':
      if (uri.pathSegments.isEmpty) return '/bills';
      if (uri.pathSegments.first == 'pay') {
        final billerType = uri.queryParameters['billerType'];
        final customerId = uri.queryParameters['customerId'];
        if (billerType != null && customerId != null) {
          return '/bills/enter-id/$billerType?customerId=$customerId';
        }
      }
      if (uri.pathSegments.first == 'receipt') {
        final reference = uri.pathSegments.length > 1 ? uri.pathSegments[1] : null;
        if (reference != null) {
          return '/bills/history?reference=$reference';
        }
      }
      return '/bills';
    default:
      return null;
  }
}
```

## Navigation Guards
```dart
// Route-level bill payment guard
class BillPaymentGuard extends RedirectGuard {
  @override
  Future<String?> redirect(BuildContext context, GoRouterState state) async {
    final authState = ref.read(authStateProvider);

    // Must be logged in
    if (authState.isLoggedIn == false) {
      return '/auth/login?redirect=${state.uri}';
    }

    // Bill payment requires at least KYC Level 1
    if (authState.kycLevel < 1) {
      return '/auth/kyc-upgrade?feature=bills';
    }

    // High-value bill payment (>200K SYP) requires KYC Level 2
    if (state.matchedLocation.startsWith('/bills/detail')) {
      final billAmount = state.extra is Bill ? (state.extra as Bill).totalDue : 0;
      if (billAmount > 200000 && authState.kycLevel < 2) {
        return '/auth/kyc-upgrade?feature=high-value-bills';
      }
    }

    return null;
  }
}
```
