# Agent Network Flutter Navigation

## Route Configuration (GoRouter)

```dart
final agentRouter = GoRouter(
  initialLocation: '/agent/login',
  routes: [
    // Auth routes (no bottom nav)
    GoRoute(
      path: '/agent/login',
      name: 'agentLogin',
      builder: (context, state) => const AgentLoginScreen(),
    ),
    GoRoute(
      path: '/agent/pin-change',
      name: 'agentPinChange',
      builder: (context, state) => const AgentPinChangeScreen(),
    ),

    // Main shell with bottom navigation
    StatefulShellRoute.indexedStack(
      builder: (context, state, navigationShell) =>
          AgentShell(navigationShell: navigationShell),
      branches: [
        // Tab 0: الإيداع (Cash-in)
        StatefulShellBranch(
          routes: [
            GoRoute(
              path: '/agent/cash-in',
              name: 'agentCashIn',
              builder: (context, state) => const CashInScreen(),
              routes: [
                GoRoute(
                  path: 'receipt',
                  name: 'cashInReceipt',
                  pageBuilder: (context, state) => BottomSheetPage(
                    child: ReceiptPreviewScreen(
                      transaction: state.extra as AgentTransaction,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),

        // Tab 1: السحب (Cash-out)
        StatefulShellBranch(
          routes: [
            GoRoute(
              path: '/agent/cash-out',
              name: 'agentCashOut',
              builder: (context, state) => const CashOutScreen(),
              routes: [
                GoRoute(
                  path: 'receipt',
                  name: 'cashOutReceipt',
                  pageBuilder: (context, state) => BottomSheetPage(
                    child: ReceiptPreviewScreen(
                      transaction: state.extra as AgentTransaction,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),

        // Tab 2: الصندوق (Float)
        StatefulShellBranch(
          routes: [
            GoRoute(
              path: '/agent/float',
              name: 'agentFloat',
              builder: (context, state) => const FloatManagementScreen(),
              routes: [
                GoRoute(
                  path: 'top-up',
                  name: 'agentFloatTopUp',
                  builder: (context, state) => const FloatTopUpScreen(),
                ),
                GoRoute(
                  path: 'agent-transfer',
                  name: 'agentFloatTransfer',
                  builder: (context, state) => const FloatTransferScreen(),
                ),
              ],
            ),
          ],
        ),

        // Tab 3: العمليات (History)
        StatefulShellBranch(
          routes: [
            GoRoute(
              path: '/agent/history',
              name: 'agentHistory',
              builder: (context, state) => const TransactionHistoryScreen(),
              routes: [
                GoRoute(
                  path: 'transaction/:id',
                  name: 'agentTransactionDetail',
                  builder: (context, state) => TransactionDetailScreen(
                    transactionId: state.pathParameters['id']!,
                  ),
                ),
                GoRoute(
                  path: 'commission',
                  name: 'agentCommission',
                  builder: (context, state) => const CommissionScreen(),
                ),
              ],
            ),
          ],
        ),
      ],
    ),

    // Settings and profile (no bottom nav)
    GoRoute(
      path: '/agent/profile',
      name: 'agentProfile',
      builder: (context, state) => const AgentProfileScreen(),
    ),
    GoRoute(
      path: '/agent/support',
      name: 'agentSupport',
      builder: (context, state) => const AgentSupportScreen(),
    ),
  ],

  // Redirect guards
  redirect: (context, state) {
    final authState = ref.read(agentAuthProvider);
    final isLoggedIn = authState.valueOrNull?.isAuthenticated ?? false;
    final isLoginRoute = state.matchedLocation == '/agent/login';

    if (!isLoggedIn && !isLoginRoute) {
      return '/agent/login';
    }
    if (isLoggedIn && isLoginRoute) {
      return '/agent/cash-in';
    }
    return null;
  },
);
```

## Navigation Flow Map
```
AgentLogin
  ├── Phone + PIN → /agent/cash-in (home)
  │
  ├── Cash-in Tab (default home)
  │   ├── Complete cash-in → success → /agent/cash-in/receipt (bottom sheet)
  │   └── → print → /agent/cash-in (reset)
  │
  ├── Cash-out Tab
  │   ├── Complete cash-out → handover → /agent/cash-out/receipt (bottom sheet)
  │   └── → print → /agent/cash-out (reset)
  │
  ├── Float Tab
  │   ├── Tap top-up → /agent/float/top-up
  │   │   └── Complete → /agent/float
  │   ├── Tap transfer → /agent/float/agent-transfer
  │   │   └── Complete → /agent/float
  │   └── Tap notification → /agent/float/top-up (pre-filled)
  │
  ├── History Tab
  │   ├── Tap transaction → /agent/history/transaction/:id
  │   │   └── Share receipt → system share sheet
  │   ├── Tap commission → /agent/history/commission
  │   └── Tap export → date picker → CSV saved
  │
  ├── Low Float Alert (system-triggered)
  │   └── Dialog → "نعم" → /agent/float/top-up
  │
  └── Deep Links
      ├── beza-agent://cash-in?phone=0961234567&amount=100000
      ├── beza-agent://float/low
      ├── beza-agent://transaction/CI-20260601-87142
      └── beza-agent://profile
```

## Offline Fallback Routes
```dart
// When connectivity is lost, certain routes show offline-optimized versions
// instead of blocking navigation

final agentRouter = GoRouter(
  // ...base routes

  // Error handler for offline scenarios
  errorBuilder: (context, state) {
    if (state.error is OfflineException) {
      return const OfflineFallbackScreen(
        message: "الخدمة غير متاحة حالياً — ستتم المعاملة عند الاتصال",
        retryLabel: "إعادة المحاولة",
      );
    }
    return const ErrorScreen(message: "حدث خطأ غير متوقع");
  },
);
```

## USSD Integration Paths
```dart
// USSD codes for agent operations (backup when POS app is unavailable)
// Agent dials these codes from their registered phone number

// *789*1*{phone}*{amount*{pin}#  →  Cash-in
// *789*2*{phone}*{amount}*{pin}#  →  Cash-out
// *789*3#                          →  Float balance
// *789*4#                          →  Today's commission
// *789*5*{amount}*{pin}#          →  Float top-up from wallet

// These USSD paths are handled at the telco level, but the app can
// trigger them via Android's `tel:` intent as fallback:
void triggerUssdCashIn(String phone, int amount) async {
  final encoded = Uri.encodeComponent('*789*1*$phone*$amount#');
  await launchUrl(Uri.parse('tel:$encoded'));
}
```

## Navigation Guards
```dart
// Route-level guard for inactive/suspended agents
class AgentActiveGuard extends RedirectGuard {
  @override
  Future<String?> redirect(BuildContext context, GoRouterState state) async {
    final authState = ref.read(agentAuthProvider);
    final agent = authState.valueOrNull?.agent;
    if (agent == null) return '/agent/login';

    switch (agent.status) {
      case AgentStatus.PENDING:
        return '/agent/pending';  // Special pending screen
      case AgentStatus.SUSPENDED:
        return '/agent/suspended';  // Show suspension info
      case AgentStatus.TERMINATED:
        return '/agent/terminated';
      case AgentStatus.ACTIVE:
        return null;  // Allow navigation
    }
  }
}

// Daily limit guard
class AgentDailyLimitGuard extends RedirectGuard {
  @override
  Future<String?> redirect(BuildContext context, GoRouterState state) async {
    if (state.matchedLocation == '/agent/cash-out') {
      final floatState = ref.read(agentFloatProvider);
      final dailyCashOut = floatState.valueOrNull?.dailyCashOutTotal ?? 0;
      final tier = ref.read(agentAuthProvider).valueOrNull?.agent.tier;
      final limit = tier?.dailyCashOutLimit ?? 2000000;
      if (dailyCashOut >= limit) {
        return '/agent/limit-reached';
      }
    }
    return null;
  }
}
```
