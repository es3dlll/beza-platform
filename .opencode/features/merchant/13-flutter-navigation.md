# Merchant Flutter Navigation

## Route Configuration (GoRouter)

```dart
final merchantRouter = GoRouter(
  initialLocation: '/merchant',
  routes: [
    ShellRoute(
      builder: (context, state, child) => MerchantShell(child: child),
      routes: [
        GoRoute(
          path: '/merchant',
          name: 'merchantHome',
          builder: (context, state) => const MerchantHomeScreen(),
        ),
        GoRoute(
          path: '/merchant/qr',
          name: 'qrDisplay',
          builder: (context, state) => const QrDisplayScreen(),
          routes: [
            GoRoute(
              path: 'share',
              name: 'qrShare',
              pageBuilder: (context, state) => BottomSheetPage(
                child: QrShareSheet(),
              ),
            ),
          ],
        ),
        GoRoute(
          path: '/merchant/payment-link',
          name: 'paymentLink',
          builder: (context, state) => const PaymentLinkScreen(),
          routes: [
            GoRoute(
              path: 'result',
              name: 'paymentLinkResult',
              builder: (context, state) => PaymentLinkResultScreen(
                link: state.extra as PaymentLinkResult,
              ),
            ),
          ],
        ),
        GoRoute(
          path: '/merchant/transactions',
          name: 'transactionHistory',
          builder: (context, state) => const TransactionHistoryScreen(),
        ),
        GoRoute(
          path: '/merchant/transaction/:id',
          name: 'transactionDetail',
          builder: (context, state) => TransactionDetailScreen(
            transactionId: state.pathParameters['id']!,
          ),
        ),
        GoRoute(
          path: '/merchant/settlements',
          name: 'settlementHistory',
          builder: (context, state) => const SettlementHistoryScreen(),
        ),
        GoRoute(
          path: '/merchant/settlement/:id',
          name: 'settlementDetail',
          builder: (context, state) => SettlementDetailScreen(
            settlementId: state.pathParameters['id']!,
          ),
        ),
        GoRoute(
          path: '/merchant/settings',
          name: 'merchantSettings',
          builder: (context, state) => const MerchantSettingsScreen(),
          routes: [
            GoRoute(
              path: 'webhook',
              name: 'webhookConfig',
              builder: (context, state) => const WebhookConfigScreen(),
            ),
          ],
        ),
      ],
    ),
    // Registration flow (outside shell, no bottom nav)
    GoRoute(
      path: '/merchant/register',
      name: 'merchantRegister',
      builder: (context, state) => const PhoneEntryScreen(),
      routes: [
        GoRoute(path: 'otp', name: 'otpVerification', builder: ...),
        GoRoute(path: 'pin', name: 'pinCreation', builder: ...),
        GoRoute(path: 'business', name: 'businessInfo', builder: ...),
        GoRoute(path: 'documents', name: 'documentUpload', builder: ...),
        GoRoute(path: 'pending', name: 'verificationPending', builder: ...),
      ],
    ),
  ],
);
```

## Navigation Flow Map
```
MerchantHome
  ├── Tap "عرض QR" → /merchant/qr
  │   └── Tap "مشاركة" → /merchant/qr/share (bottom sheet)
  │
  ├── Tap "رابط دفع" → /merchant/payment-link
  │   └── Create → /merchant/payment-link/result (share actions)
  │
  ├── Tap "المعاملات" → /merchant/transactions
  │   └── Tap transaction → /merchant/transaction/:id
  │
  ├── Tap التسوية → /merchant/settlements
  │   └── Tap settlement → /merchant/settlement/:id
  │
  ├── Tap الإعدادات → /merchant/settings
  │   └── Tap Webhook → /merchant/settings/webhook
  │
  └── Tap تذييل التسوية → /merchant/settlement/today (quick link)

Registration Flow (no auth required):
  /merchant/register → /merchant/register/otp
    → /merchant/register/pin
    → /merchant/register/business
    → /merchant/register/documents
    → /merchant/register/pending
    → (verification approved) → /merchant (home)
```

## Deep Links
```dart
// beza-merchant://payment-link/create?amount=45000
// beza-merchant://transaction/TXN-MER-ABC123
// beza-merchant://settlement/2026-06-01

final merchantRouter = GoRouter(
  redirect: (context, state) {
    final uri = Uri.parse(state.uri.toString());
    if (uri.scheme == 'beza-merchant') {
      return _handleDeepLink(uri);
    }
    return null;
  },
);

String? _handleDeepLink(Uri uri) {
  switch (uri.host) {
    case 'payment-link':
      final amount = uri.queryParameters['amount'];
      return '/merchant/payment-link?amount=$amount';
    case 'transaction':
      final id = uri.pathSegments.first;
      return '/merchant/transaction/$id';
    case 'settlement':
      final date = uri.pathSegments.first;
      return '/merchant/settlement?date=$date';
    default:
      return '/merchant';
  }
}
```

## Navigation Guards
```dart
class MerchantAuthGuard extends RedirectGuard {
  @override
  Future<String?> redirect(BuildContext context, GoRouterState state) async {
    final authState = ref.read(merchantAuthProvider);
    if (!authState.isLoggedIn && !state.matchedLocation.startsWith('/merchant/register')) {
      return '/merchant/register';
    }
    if (authState.isLoggedIn && authState.status != MerchantStatus.VERIFIED) {
      if (!state.matchedLocation.contains('/register')) {
        return '/merchant/register/pending';
      }
    }
    return null;
  }
}
```
