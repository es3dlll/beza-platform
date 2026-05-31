# Remittance Flutter Navigation

## Route Configuration (GoRouter)

```dart
final router = GoRouter(
  initialLocation: '/remittance',
  routes: [
    ShellRoute(
      builder: (context, state, child) => AppShell(child: child),
      routes: [
        GoRoute(
          path: '/remittance',
          name: 'remittanceHome',
          builder: (context, state) => const SendRemittanceScreen(),
        ),
        GoRoute(
          path: '/remittance/send',
          name: 'sendRemittance',
          builder: (context, state) => const SendRemittanceScreen(),
          routes: [
            GoRoute(
              path: 'confirm',
              name: 'sendConfirm',
              pageBuilder: (context, state) => BottomSheetPage(
                child: ConfirmRemittanceSheet(
                  formState: state.extra as RemittanceFormState,
                ),
              ),
            ),
            GoRoute(
              path: 'result/:status',
              name: 'sendResult',
              builder: (context, state) => RemittanceResultScreen(
                status: state.pathParameters['status']!,
              ),
            ),
          ],
        ),
        GoRoute(
          path: '/remittance/beneficiaries',
          name: 'beneficiaries',
          builder: (context, state) => const BeneficiaryManagementScreen(),
        ),
        GoRoute(
          path: '/remittance/beneficiaries/add',
          name: 'addBeneficiary',
          builder: (context, state) => const AddBeneficiaryScreen(),
        ),
        GoRoute(
          path: '/remittance/beneficiaries/:id/edit',
          name: 'editBeneficiary',
          builder: (context, state) => AddBeneficiaryScreen(
            beneficiaryId: state.pathParameters['id'],
          ),
        ),
        GoRoute(
          path: '/remittance/recurring',
          name: 'recurringList',
          builder: (context, state) => const RecurringTransferListScreen(),
        ),
        GoRoute(
          path: '/remittance/recurring/create',
          name: 'createRecurring',
          builder: (context, state) => const CreateRecurringTransferScreen(),
        ),
        GoRoute(
          path: '/remittance/history',
          name: 'remittanceHistory',
          builder: (context, state) => const RemittanceHistoryScreen(),
        ),
        GoRoute(
          path: '/remittance/transfer/:id',
          name: 'remittanceDetail',
          builder: (context, state) => RemittanceDetailScreen(
            transferId: state.pathParameters['id']!,
          ),
        ),
        GoRoute(
          path: '/remittance/request',
          name: 'requestMoney',
          builder: (context, state) => const RequestMoneyScreen(),
        ),
        GoRoute(
          path: '/remittance/corridors',
          name: 'corridorAdmin',
          builder: (context, state) => const CorridorAdminScreen(),
        ),
      ],
    ),
  ],
);
```

## Navigation Flow Map
```
SendRemittance
  ├── Select Beneficiary
  ├── Enter Amount + Select Currency
  ├── Lock FX Rate (optional)
  ├── Confirm → /remittance/send/confirm (bottom sheet)
  │   ├── Success → /remittance/send/result/success
  │   │   ├── View Detail → /remittance/transfer/:id
  │   │   └── Repeat → back to /remittance/send (pre-filled)
  │   └── Fail → /remittance/send/result/fail
  │       └── Retry → back to confirmation
  │
  ├── Beneficiaries → /remittance/beneficiaries
  │   ├── Add → /remittance/beneficiaries/add
  │   └── Edit → /remittance/beneficiaries/:id/edit
  │
  ├── Recurring → /remittance/recurring
  │   └── Create → /remittance/recurring/create
  │
  ├── History → /remittance/history
  │   └── Tap Transfer → /remittance/transfer/:id
  │
  ├── Request → /remittance/request
  │
  └── Corridors (Admin) → /remittance/corridors
```

## Deep Links
```dart
// beza-app://send?to=+963912345678&amount=50000&currency=SYP
// beza-app://request?amount=100&currency=EUR
// beza-app://remittance/TXN-ABC123
// beza-app://beneficiaries/add

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
      final currency = uri.queryParameters['currency'];
      return '/remittance/send?to=$to&amount=$amount&currency=$currency';
    case 'request':
      final amount = uri.queryParameters['amount'];
      final currency = uri.queryParameters['currency'];
      return '/remittance/request?amount=$amount&currency=$currency';
    case 'remittance':
      final id = uri.pathSegments.first;
      return '/remittance/transfer/$id';
    default:
      return null;
  }
}
```

## Navigation Guards
```dart
class DiasporaAuthGuard extends RedirectGuard {
  @override
  Future<String?> redirect(BuildContext context, GoRouterState state) async {
    final authState = ref.read(authStateProvider);
    if (authState.isLoggedIn == false) {
      return '/auth/login?redirect=${state.uri}';
    }

    // Diaspora remittance requires at least KYC Level 2
    if (state.matchedLocation.startsWith('/remittance/send') &&
        state.uri.toString().contains('currency=EUR') ||
        state.uri.toString().contains('currency=USD')) {
      if (authState.kycLevel < 2) {
        return '/auth/kyc-upgrade?level=2';
      }
    }

    // Admin corridors require role
    if (state.matchedLocation.startsWith('/remittance/corridors')) {
      if (!authState.isAdmin) {
        return '/403';
      }
    }

    return null;
  }
}
```
