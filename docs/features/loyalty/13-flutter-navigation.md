# Loyalty Flutter Navigation

## Route Configuration (GoRouter)
```dart
final router = GoRouter(
  initialLocation: '/loyalty',
  routes: [
    ShellRoute(
      builder: (context, state, child) => AppShell(child: child),
      routes: [
        GoRoute(
          path: '/loyalty',
          name: 'loyaltyHub',
          builder: (context, state) => const LoyaltyHubScreen(),
        ),
        GoRoute(
          path: '/loyalty/tier',
          name: 'tierProgress',
          builder: (context, state) => const TierProgressScreen(),
        ),
        GoRoute(
          path: '/loyalty/rewards',
          name: 'rewardsCatalog',
          builder: (context, state) => const RewardsCatalogScreen(),
          routes: [
            GoRoute(
              path: 'redeem',
              name: 'redeemConfirm',
              pageBuilder: (context, state) => BottomSheetPage(
                child: RedemptionConfirmationSheet(
                  reward: state.extra as Reward,
                ),
              ),
            ),
            GoRoute(
              path: 'result/:status',
              name: 'redeemResult',
              builder: (context, state) => RedemptionResultScreen(
                status: state.pathParameters['status']!,
              ),
            ),
          ],
        ),
        GoRoute(
          path: '/loyalty/history',
          name: 'pointsHistory',
          builder: (context, state) => const PointsHistoryScreen(),
        ),
        GoRoute(
          path: '/loyalty/merchant',
          name: 'merchantCampaigns',
          builder: (context, state) => const MerchantCampaignListScreen(),
          routes: [
            GoRoute(
              path: ':campaignId',
              name: 'campaignDetail',
              builder: (context, state) => MerchantCampaignDetailScreen(
                campaignId: state.pathParameters['campaignId']!,
              ),
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
LoyaltyHub
  ├── Tap Points Card → /loyalty/history
  ├── Tap Tier Card → /loyalty/tier
  ├── Tap Reward → /loyalty/rewards/redeem (bottom sheet)
  │   └── Confirm PIN → /loyalty/rewards/result/success
  │   └── Error → /loyalty/rewards/result/fail
  ├── Tap "View All" → /loyalty/rewards
  ├── Tap Merchant Campaign → /loyalty/merchant/:id
  └── Tap "Merchant Tools" → /loyalty/merchant

App Shell Tabs:
  الرئيسية | أرسل | المكافآت | المزيد
  (Loyalty tab badge: points count or tier icon)
```

## Deep Links
```dart
// beza-app://loyalty (open hub)
// beza-app://loyalty/rewards (open catalog)
// beza-app://loyalty/history (open history)
// beza-app://loyalty/redeem?reward=airtime_5000 (pre-select reward)

final router = GoRouter(
  redirect: (context, state) {
    final uri = Uri.parse(state.uri.toString());
    if (uri.scheme == 'beza-app' && uri.host == 'loyalty') {
      return _handleDeepLink(uri);
    }
    return null;
  },
);

String? _handleDeepLink(Uri uri) {
  switch (uri.path) {
    case '/rewards':
      return '/loyalty/rewards';
    case '/history':
      return '/loyalty/history';
    case '/redeem':
      final reward = uri.queryParameters['reward'];
      return '/loyalty/rewards/redeem?reward=$reward';
    default:
      return '/loyalty';
  }
}
```

## Navigation Guards
```dart
class MerchantGuard extends RedirectGuard {
  @override
  Future<String?> redirect(BuildContext context, GoRouterState state) async {
    if (state.matchedLocation.startsWith('/loyalty/merchant')) {
      final user = ref.read(authStateProvider);
      if (!user.isMerchant) {
        return '/loyalty'; // Non-merchants can't access merchant tools
      }
    }
    return null;
  }
}
```
