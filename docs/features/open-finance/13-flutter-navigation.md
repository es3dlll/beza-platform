# Open Finance Flutter Navigation

## Route Configuration (GoRouter)
```dart
final router = GoRouter(
  initialLocation: '/developer/dashboard',
  routes: [
    ShellRoute(
      builder: (context, state, child) => DeveloperShell(child: child),
      routes: [
        GoRoute(
          path: '/developer/dashboard',
          name: 'developerDashboard',
          builder: (context, state) => const DashboardScreen(),
        ),
        GoRoute(
          path: '/developer/keys',
          name: 'apiKeys',
          builder: (context, state) => const ApiKeyListScreen(),
        ),
        GoRoute(
          path: '/developer/webhooks',
          name: 'webhooks',
          builder: (context, state) => const WebhookConfigScreen(),
          routes: [
            GoRoute(
              path: 'delivery/:id',
              name: 'webhookDeliveryDetail',
              builder: (context, state) => WebhookDeliveryScreen(
                deliveryId: state.pathParameters['id']!,
              ),
            ),
          ],
        ),
        GoRoute(
          path: '/developer/sandbox',
          name: 'sandbox',
          builder: (context, state) => const SandboxScreen(),
        ),
        GoRoute(
          path: '/developer/docs',
          name: 'apiDocs',
          builder: (context, state) => const ApiReferenceScreen(),
          routes: [
            GoRoute(
              path: ':endpoint',
              name: 'apiEndpointDetail',
              builder: (context, state) => EndpointDetailScreen(
                endpointId: state.pathParameters['endpoint']!,
              ),
            ),
          ],
        ),
        GoRoute(
          path: '/developer/playground',
          name: 'playground',
          builder: (context, state) => const PlaygroundScreen(),
        ),
      ],
    ),
  ],
);
```

## Navigation Flow Map
```
DeveloperPortal
  ├── Tab: Dashboard → /developer/dashboard
  │   └── Tap request → /developer/request/:id
  │
  ├── Tab: Keys → /developer/keys
  │   └── FAB → Create key bottom sheet
  │
  ├── Tab: Webhooks → /developer/webhooks
  │   └── Tap delivery → /developer/webhooks/delivery/:id
  │
  ├── Tab: Sandbox → /developer/sandbox
  │
  └── Tab: Docs → /developer/docs
      └── Tap endpoint → /developer/docs/:endpoint
          └── Tap "Try It" → /developer/playground
```

## Deep Links
```dart
// beza-dev://sandbox/reset
// beza-dev://keys/create
// beza-dev://webhooks/delivery/:id
// beza-dev://docs/payments

final router = GoRouter(
  redirect: (context, state) {
    final uri = Uri.parse(state.uri.toString());
    if (uri.scheme == 'beza-dev') {
      return _handleDeepLink(uri);
    }
    return null;
  },
);

String? _handleDeepLink(Uri uri) {
  switch (uri.host) {
    case 'sandbox':
      return '/developer/sandbox${uri.path}';
    case 'keys':
      return '/developer/keys${uri.path}';
    case 'webhooks':
      return '/developer/webhooks${uri.path}';
    case 'docs':
      return '/developer/docs${uri.path}';
    default:
      return '/developer/dashboard';
  }
}
```

## Navigation Guards
```dart
class DeveloperAuthGuard extends RedirectGuard {
  @override
  Future<String?> redirect(BuildContext context, GoRouterState state) async {
    final authState = ref.read(developerAuthProvider);
    if (!authState.isAuthenticated) {
      return '/developer/login?redirect=${state.uri}';
    }
    // Check KYC status for production access
    if (state.matchedLocation.startsWith('/developer/keys') && 
        !authState.isKycApproved) {
      return '/developer/kyc-pending';
    }
    return null;
  }
}
```
