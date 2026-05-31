# Wallet Flutter Navigation

## Route Configuration (GoRouter)

```dart
final router = GoRouter(
  initialLocation: '/wallet',
  routes: [
    ShellRoute(
      builder: (context, state, child) => AppShell(child: child),
      routes: [
        GoRoute(
          path: '/wallet',
          name: 'walletHome',
          builder: (context, state) => const WalletHomeScreen(),
        ),
        GoRoute(
          path: '/send',
          name: 'sendMoney',
          builder: (context, state) => const SendMoneyScreen(),
          routes: [
            GoRoute(
              path: 'confirm',
              name: 'sendConfirm',
              pageBuilder: (context, state) => BottomSheetPage(
                child: ConfirmTransferScreen(
                  recipient: state.extra as String,
                ),
              ),
            ),
            GoRoute(
              path: 'result/:status',
              name: 'sendResult',
              builder: (context, state) => TransferResultScreen(
                status: state.pathParameters['status']!,
              ),
            ),
          ],
        ),
        GoRoute(
          path: '/request',
          name: 'requestMoney',
          builder: (context, state) => const RequestMoneyScreen(),
        ),
        GoRoute(
          path: '/history',
          name: 'transactionHistory',
          builder: (context, state) => const TransactionHistoryScreen(),
        ),
        GoRoute(
          path: '/transaction/:id',
          name: 'transactionDetail',
          builder: (context, state) => TransactionDetailScreen(
            transactionId: state.pathParameters['id']!,
          ),
        ),
        GoRoute(
          path: '/bills',
          name: 'billPayment',
          builder: (context, state) => const BillPaymentScreen(),
        ),
        GoRoute(
          path: '/agent-locator',
          name: 'agentLocator',
          builder: (context, state) => const AgentLocatorScreen(),
        ),
      ],
    ),
  ],
);
```

## Navigation Flow Map
```
WalletHome
  ├── Tap Send → /send
  │   └── Confirm → /send/confirm (bottom sheet)
  │       └── Success → /send/result/success
  │       └── Fail → /send/result/fail
  │
  ├── Tap Request → /request
  │   └── Sent → WalletHome (snackbar)
  │
  ├── Tap Pay → /bills
  │   └── Select category → /bills/category/:id
  │       └── Enter ID → /bills/detail/:billerId
  │           └── Confirm → Bottom Sheet
  │               └── Result → /bills/result/:status
  │
  ├── Tap Agent → /agent-locator
  │   └── Select agent → /agent/:id (detail bottom sheet)
  │
  ├── Tap Savings → /savings
  │   (delegated to Savings feature)
  │
  ├── Tap Transaction → /transaction/:id
  │
  └── Tap View All → /history
      └── Tap Transaction → /transaction/:id
```

## Deep Links
```dart
// Incoming deep links handled by GoRouter redirect
// beza-app://send?to=+963912345678&amount=25000
// beza-app://request?amount=10000
// beza-app://transaction/TXN-ABC123
// beza-app://bill/electricity

final router = GoRouter(
  redirect: (context, state) {
    final uri = Uri.parse(state.uri.toString());
    if (uri.scheme == 'beza-app') {
      return _handleDeepLink(uri);
    }
    return null;
  },
);

String? _handleDeepLink(Uri uri) {
  switch (uri.host) {
    case 'send':
      final to = uri.queryParameters['to'];
      final amount = uri.queryParameters['amount'];
      return '/send?to=$to&amount=$amount';
    case 'transaction':
      final id = uri.pathSegments.first;
      return '/transaction/$id';
    default:
      return null;
  }
}
```

## Navigation Guards
```dart
// Route-level auth guard
class AuthGuard extends RedirectGuard {
  @override
  Future<String?> redirect(BuildContext context, GoRouterState state) async {
    final authState = ref.read(authStateProvider);
    if (authState.isLoggedIn == false) {
      return '/auth/login?redirect=${state.uri}';
    }
    if (authState.kycLevel < _requiredLevel(state)) {
      return '/auth/kyc-upgrade';
    }
    return null;
  }

  int _requiredLevel(GoRouterState state) {
    if (state.matchedLocation.startsWith('/send')) return 2;
    return 1;
  }
}
```
