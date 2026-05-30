# Flutter Engineering Spec

## Technology Stack
| Component | Choice | Version |
|-----------|--------|---------|
| Framework | Flutter | 3.x+ |
| Language | Dart | 3.x+ |
| State Management | Riverpod | 2.x |
| Navigation | GoRouter | 12.x |
| HTTP Client | Dio | 5.x |
| Local DB | SQLite (drift) | Latest |
| Secure Storage | flutter_secure_storage | Latest |
| Biometrics | local_auth | Latest |
| Maps | Mapbox / Google Maps | Latest |
| Push | Firebase Messaging | Latest |
| Charts | fl_chart | Latest |
| Animations | Lottie + Rive | Latest |

## Architecture: Feature-First Clean Architecture
```
lib/
├── core/
│   ├── api/
│   │   ├── dio_client.dart          # Dio instance, interceptors
│   │   ├── auth_interceptor.dart    # JWT injection, refresh
│   │   ├── retry_interceptor.dart   # Exponential backoff retry
│   │   └── api_exceptions.dart      # Error mapping
│   ├── auth/
│   │   ├── auth_state.dart          # AuthNotifier
│   │   ├── auth_repository.dart     # Login, register, refresh
│   │   └── auth_guard.dart          # Route guard
│   ├── design/
│   │   ├── app_theme.dart           # ThemeData configuration
│   │   ├── app_colors.dart          # Color constants
│   │   ├── app_typography.dart      # Text styles
│   │   ├── app_spacing.dart         # Spacing constants
│   │   └── widgets/                 # Shared widgets
│   │       ├── beza_button.dart
│   │       ├── beza_text_field.dart
│   │       ├── beza_balance_card.dart
│   │       ├── beza_transaction_tile.dart
│   │       ├── skeleton_loader.dart
│   │       ├── error_state.dart
│   │       ├── empty_state.dart
│   │       └── loading_overlay.dart
│   ├── router/
│   │   └── app_router.dart          # GoRouter configuration
│   ├── services/
│   │   ├── biometric_service.dart
│   │   ├── notification_service.dart
│   │   ├── connectivity_service.dart
│   │   └── location_service.dart
│   ├── storage/
│   │   ├── secure_storage.dart      # Token, PIN storage
│   │   └── local_database.dart      # SQLite database
│   └── utils/
│       ├── formatters.dart          # Amount, date, phone
│       ├── validators.dart          # Input validation
│       └── constants.dart           # App-wide constants
│
├── features/
│   ├── auth/
│   │   ├── data/
│   │   │   ├── datasources/
│   │   │   ├── models/
│   │   │   └── repositories/
│   │   ├── domain/
│   │   │   ├── entities/
│   │   │   ├── repositories/
│   │   │   └── usecases/
│   │   └── presentation/
│   │       ├── providers/
│   │       ├── screens/
│   │       └── widgets/
│   │
│   ├── wallet/
│   │   └── (same structure)
│   │
│   ├── agent/
│   ├── merchant/
│   ├── bills/
│   ├── savings/
│   ├── cards/
│   ├── fx/
│   ├── marketplace/
│   ├── loyalty/
│   └── profile/
│
├── app.dart
└── main.dart
```

## State Management (Riverpod)
```dart
// Provider hierarchy pattern
final dioClientProvider = Provider<Dio>((ref) {
  final dio = Dio(BaseOptions(baseUrl: AppConstants.apiUrl));
  dio.interceptors.add(ref.read(authInterceptorProvider));
  dio.interceptors.add(LogInterceptor(requestBody: true));
  return dio;
});

final walletRepositoryProvider = Provider<WalletRepository>((ref) {
  return WalletRepositoryImpl(ref.read(dioClientProvider));
});

final walletBalanceProvider = AsyncNotifierProvider<WalletBalanceNotifier, BalanceState>(
  WalletBalanceNotifier.new,
);

final transactionListProvider = AsyncNotifierProvider<TransactionListNotifier, List<Transaction>>(
  TransactionListNotifier.new,
);
```

## Offline-First Strategy
```dart
// SQLite schema for offline queue
CREATE TABLE pending_actions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  action_type TEXT NOT NULL,          // 'send_money', 'pay_bill', etc.
  payload TEXT NOT NULL,               // JSON serialized request
  status TEXT NOT NULL DEFAULT 'pending',  // pending, processing, completed, failed
  retry_count INTEGER DEFAULT 0,
  max_retries INTEGER DEFAULT 3,
  created_at TEXT NOT NULL,
  last_attempt_at TEXT
);

// Sync service
class SyncService {
  Future<void> processQueue() async {
    final pending = await db.query(
      'SELECT * FROM pending_actions WHERE status = ?',
      ['pending'],
      orderBy: 'created_at ASC',
    );

    for (final action in pending) {
      await _processAction(action);
    }
  }

  Future<void> _processAction(Map<String, dynamic> action) async {
    await db.update('pending_actions', 
      {'status': 'processing', 'last_attempt_at': DateTime.now().toIso8601String()},
      where: 'id = ?', whereArgs: [action['id']],
    );

    try {
      final response = await dio.post('/api/v1/${action['action_type']}',
        data: jsonDecode(action['payload']),
      );
      await db.delete('pending_actions', where: 'id = ?', whereArgs: [action['id']]);
    } catch (e) {
      final retryCount = action['retry_count'] + 1;
      if (retryCount >= action['max_retries']) {
        await db.update('pending_actions',
          {'status': 'failed', 'retry_count': retryCount},
          where: 'id = ?', whereArgs: [action['id']],
        );
        // Notify user
      } else {
        await db.update('pending_actions',
          {'status': 'pending', 'retry_count': retryCount},
          where: 'id = ?', whereArgs: [action['id']],
        );
      }
    }
  }
}
```

## Navigation Graph
```dart
final appRouter = GoRouter(
  initialLocation: '/splash',
  redirect: (context, state) {
    final auth = ref.read(authStateProvider);
    if (auth.isLoading) return null;
    if (!auth.isLoggedIn && state.matchedLocation != '/auth/login') {
      return '/auth/login';
    }
    return null;
  },
  routes: [
    GoRoute(path: '/splash', builder: (_, __) => const SplashScreen()),

    // Auth
    GoRoute(path: '/auth/login', builder: (_, __) => const LoginScreen()),
    GoRoute(path: '/auth/register', builder: (_, __) => const RegisterScreen()),
    GoRoute(path: '/auth/pin-setup', builder: (_, __) => const PinSetupScreen()),

    // Main Shell (Bottom Navigation)
    ShellRoute(
      builder: (_, __, child) => AppShell(child: child),
      routes: [
        GoRoute(path: '/wallet', builder: (_, __) => const WalletHomeScreen()),
        GoRoute(path: '/send', builder: (_, __) => const SendMoneyScreen()),
        GoRoute(path: '/bills', builder: (_, __) => const BillsScreen()),
        GoRoute(path: '/more', builder: (_, __) => const MoreScreen()),

        // Nested routes
        GoRoute(path: '/transactions/:id', builder: (_, state) =>
          TransactionDetailScreen(id: state.pathParameters['id']!)),
        GoRoute(path: '/savings', builder: (_, __) => const SavingsScreen()),
        GoRoute(path: '/cards', builder: (_, __) => const CardsScreen()),
        GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen()),
        GoRoute(path: '/settings', builder: (_, __) => const SettingsScreen()),
      ],
    ),
  ],
);
```

## Performance Targets
| Metric | Target |
|--------|--------|
| App cold start | < 2s |
| Screen navigation | < 300ms |
| API response display | < 100ms after response |
| Balance refresh | < 500ms |
| Animation frame rate | 60fps |
| App size (Android) | < 30MB |
| App size (iOS) | < 80MB |
| Memory usage | < 150MB peak |
| Crash-free rate | > 99.9% |
