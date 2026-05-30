# FX Engine Flutter Navigation

## Route Configuration (GoRouter)

```dart
final router = GoRouter(
  initialLocation: '/wallet',
  routes: [
    ShellRoute(
      builder: (context, state, child) => AppShell(child: child),
      routes: [
        // ... other feature routes
        GoRoute(
          path: '/fx',
          name: 'exchangeHome',
          builder: (context, state) => const ExchangeHomeScreen(),
        ),
        GoRoute(
          path: '/fx/convert',
          name: 'convert',
          builder: (context, state) => const ConvertScreen(),
          routes: [
            GoRoute(
              path: 'lock',
              name: 'rateLock',
              pageBuilder: (context, state) => BottomSheetPage(
                child: RateLockSheet(
                  pair: state.extra as RateLockParams,
                ),
              ),
            ),
            GoRoute(
              path: 'result/:status',
              name: 'conversionResult',
              builder: (context, state) => ConversionResultScreen(
                status: state.pathParameters['status']!,
                result: state.extra as ConversionResult?,
              ),
            ),
          ],
        ),
        GoRoute(
          path: '/fx/rate/:pair',
          name: 'rateDetail',
          builder: (context, state) => RateDetailScreen(
            pair: state.pathParameters['pair']!,
          ),
        ),
        GoRoute(
          path: '/fx/history',
          name: 'conversionHistory',
          builder: (context, state) => const ConversionHistoryScreen(),
        ),
        GoRoute(
          path: '/fx/history/:id',
          name: 'conversionDetail',
          builder: (context, state) => ConversionDetailScreen(
            conversionId: state.pathParameters['id']!,
          ),
        ),
        GoRoute(
          path: '/fx/admin',
          name: 'adminFxDashboard',
          builder: (context, state) => const AdminFXDashboard(),
        ),
      ],
    ),
  ],
);
```

## Navigation Flow Map
```
ExchangeHome
  ├── Tap Rate Card → /fx/rate/SYP-USD (expanded detail + chart)
  │
  ├── Tap "Convert" FAB → /fx/convert
  │   ├── Select wallets & amount
  │   ├── Tap "تثبيت السعر" → /fx/convert/lock (bottom sheet)
  │   │   ├── Confirm with PIN → POST /fx/convert
  │   │   │   ├── Success → /fx/convert/result/success
  │   │   │   │   ├── Tap "مشاركة" → Share sheet
  │   │   │   │   └── Tap "التالي" → WalletHome or ExchangeHome
  │   │   │   └── Fail → /fx/convert/result/fail
  │   │   │       ├── Tap "إعادة المحاولة" → retry conversion
  │   │   │       └── Tap "رجوع" → ConvertScreen
  │   │   └── Rate expired → pop sheet, show "انتهت الصلاحية"
  │   └── Back → ExchangeHome
  │
  ├── Tap "History" → /fx/history
  │   └── Tap conversion → /fx/history/:id
  │
  └── Admin (role_gate) → /fx/admin
      ├── Provider health
      ├── Rate override
      ├── Spread config
      └── CBS report
```

## Deep Links
```dart
// Incoming deep links handled by GoRouter redirect
// beza-app://fx/convert?from=SYP&to=USD&amount=5000000
// beza-app://fx/history/FX-CONV-ABC123
// beza-app://fx/admin (admin only)

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
    case 'fx':
      if (uri.pathSegments.first == 'convert') {
        final from = uri.queryParameters['from'];
        final to = uri.queryParameters['to'];
        final amount = uri.queryParameters['amount'];
        return '/fx/convert?from=$from&to=$to&amount=$amount';
      }
      if (uri.pathSegments.first == 'history') {
        final id = uri.pathSegments[1];
        return '/fx/history/$id';
      }
      return '/fx';
    default:
      return null;
  }
}
```

## Navigation Guards
```dart
class FXRouteGuard extends RedirectGuard {
  @override
  Future<String?> redirect(BuildContext context, GoRouterState state) async {
    final authState = ref.read(authStateProvider);
    if (!authState.isLoggedIn) {
      return '/auth/login?redirect=${state.uri}';
    }

    // Admin routes require admin role
    if (state.matchedLocation.startsWith('/fx/admin')) {
      if (!authState.isAdmin) {
        return '/fx'; // Redirect non-admin to FX home
      }
    }

    // Conversion requires KYC level 1+
    if (state.matchedLocation.startsWith('/fx/convert')) {
      if (authState.kycLevel < 1) {
        return '/auth/kyc-upgrade';
      }
    }

    return null;
  }
}
```
