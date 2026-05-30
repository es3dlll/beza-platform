# Flutter Conventions — Beza Platform

## Overview

The Flutter mobile app serves Beza's end users (senders, receivers) and agents across Syria. Primary language is Arabic. The app must work reliably on low-end Android devices (2GB RAM, Android 10+), on Syrian mobile networks with variable connectivity (3G/4G with frequent drops), and offline for critical operations.

## Directory Structure

```
mobile/lib/
├── main.dart
├── app/
│   ├── app.dart                # MaterialApp configuration
│   ├── router.dart             # GoRouter configuration
│   ├── theme/
│   │   ├── app_theme.dart      # Light/dark theme definitions
│   │   ├── app_colors.dart     # Color palette
│   │   └── app_text_styles.dart
│   └── translations/
│       ├── app_translations.dart
│       ├── ar.arb              # Arabic translations
│       └── en.arb              # English translations
├── core/
│   ├── network/
│   │   ├── api_client.dart     # Dio HTTP client with interceptors
│   │   ├── api_endpoints.dart  # All API endpoint constants
│   │   └── api_exceptions.dart
│   ├── storage/
│   │   ├── local_database.dart  # SQLite (sqflite/floor) setup
│   │   ├── secure_storage.dart  # flutter_secure_storage for tokens
│   │   └── cache_manager.dart   # API response cache logic
│   ├── widgets/
│   │   ├── beza_button.dart
│   │   ├── beza_text_field.dart
│   │   ├── loading_overlay.dart
│   │   ├── error_screen.dart
│   │   ├── empty_state.dart
│   │   └── syria_phone_input.dart
│   └── utils/
│       ├── validators.dart      # Syrian phone, national ID, amount
│       ├── formatters.dart      # Currency, date, number formatting
│       └── constants.dart      # API URLs, timeouts, limits
├── features/
│   ├── auth/
│   │   ├── screens/
│   │   │   ├── login_screen.dart
│   │   │   ├── register_screen.dart
│   │   │   └── verify_otp_screen.dart
│   │   ├── widgets/
│   │   ├── services/
│   │   │   └── auth_service.dart
│   │   ├── models/
│   │   │   └── user_model.dart
│   │   └── providers/
│   │       └── auth_provider.dart  # Riverpod provider
│   ├── wallet/
│   │   ├── screens/
│   │   │   ├── wallet_screen.dart
│   │   │   └── transaction_history_screen.dart
│   │   ├── widgets/
│   │   │   ├── balance_card.dart
│   │   │   └── transaction_list_item.dart
│   │   ├── services/
│   │   │   └── wallet_service.dart
│   │   ├── models/
│   │   │   ├── wallet_model.dart
│   │   │   └── transaction_model.dart
│   │   └── providers/
│   │       └── wallet_provider.dart
│   ├── transfer/
│   │   ├── screens/
│   │   │   ├── transfer_form_screen.dart
│   │   │   ├── transfer_confirmation_screen.dart
│   │   │   └── transfer_success_screen.dart
│   │   ├── widgets/
│   │   ├── services/
│   │   │   └── transfer_service.dart
│   │   ├── models/
│   │   └── providers/
│   └── agent/
│       ├── screens/
│       │   ├── agent_dashboard_screen.dart
│       │   ├── cash_in_screen.dart
│       │   └── cash_out_screen.dart
│       ├── widgets/
│       ├── services/
│       ├── models/
│       └── providers/
```

## Conventions

### State Management — Riverpod (Preferred)
- All feature modules use Riverpod for state management.
- `setState` only for local UI state (text field focus, animation controller).
- Each screen has a corresponding provider that exposes state and actions.
- Providers are auto-disposed when no longer listened to (using `autoDispose`).
- Complex async state uses `AsyncValue` with `.when()` for loading/error/data UI.

```dart
// ✅ CORRECT
final walletProvider = FutureProvider.autoDispose.family<WalletModel, String>(
  (ref, walletId) => ref.read(walletServiceProvider).getWallet(walletId),
);

class WalletScreen extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final walletId = ref.watch(currentWalletIdProvider);
    final walletAsync = ref.watch(walletProvider(walletId));

    return walletAsync.when(
      data: (wallet) => WalletBalanceCard(wallet: wallet),
      loading: () => const SkeletonLoader(),
      error: (e, _) => ErrorScreen(message: e.toString()),
    );
  }
}
```

### GoRouter Navigation
- All navigation uses GoRouter with declarative routing.
- Deep linking supported for: `/transfer/{reference}`, `/wallet/{id}`, `/agent/{agentId}`.
- Route guards for unauthenticated access redirect to login.
- ShellRoute for bottom navigation with persistent state.
- Syria-specific: back navigation preserves form state (don't clear form on pop).

### Repository Pattern for Data Access
- Feature modules have a service/API layer. Data flows: Screen → Provider → Service → Repository.
- Repository decides: return cached data first, then refresh from API (offline-first).
- Repository deserializes JSON into typed model classes.
- Never pass raw `Map<String, dynamic>` outside of service layer.

```dart
class WalletRepository {
  final ApiClient apiClient;
  final LocalDatabase localDb;

  Future<WalletModel> getWallet(String walletId) async {
    // Try cache first (offline-first)
    final cached = await localDb.getWallet(walletId);
    if (cached != null && !cached.isStale) return cached;

    // Fetch from API
    final response = await apiClient.get('/wallet/wallets/$walletId');
    final wallet = WalletModel.fromJson(response.data);

    // Cache for offline use
    await localDb.insertWallet(wallet);

    return wallet;
  }
}
```

### Arabic-First Translations
- All user-facing strings come from ARB files.
- Arabic is the default locale. English is secondary.
- Number formatting: Arabic-Indic numerals (٠-٩) for primary UI, Western numerals for data entry.
- RTL layout is the default. `Directionality` wraps all material widgets.
- Currency formatting: SYP amounts use "ل.س" suffix with Arabic-Indic digits.
- Dates formatted in Islamic calendar where culturally relevant (alongside Gregorian).

```dart
// ✅ CORRECT
Text(AppLocalizations.of(context)!.walletBalance)
Text(AppLocalizations.of(context)!.transferAmount(amount: 5000))

// ❌ WRONG
Text('رصيد المحفظة')
Text('المبلغ: 5000 ل.س')
```

### Offline-First Architecture
- All API responses cached in local SQLite database.
- Key operations (check balance, view last 50 transactions) available fully offline.
- Mutations (transfers) queued locally if no connectivity, submitted when online.
- Offline queue persisted to secure storage with encryption.
- User visible indicator of connectivity status in app bar.
- Cache freshness: wallet balance < 30 seconds, transaction list < 5 minutes, static data < 24 hours.

### Syria-Specific Mobile Considerations
- **Low bandwidth mode**: Images compressed, API payloads minimized, batch requests.
- **Network resilience**: Retry with exponential backoff (3 attempts), timeout 15s.
- **Phone verification**: OTP-based login. SMS delivery can be slow in Syria — allow 120s timeout.
- **Storage**: Minimize cache size (< 100MB). Offer "clear cache" in settings.
- **Battery**: No background polling. Refresh on app foreground only.
- **Agent features**: GPS for agent location verification, camera for barcode/NFC scanning of national IDs.
- **Security**: Biometric lock (fingerprint) for transactions > 500,000 SYP. Session timeout after 5 min idle.

### Testing Requirements

```dart
// ✅ Widget test example
void main() {
  testWidgets('WalletBalanceCard displays balance in SYP', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        localizationsDelegates: AppLocalizations.localizationsDelegates,
        supportedLocales: AppLocalizations.supportedLocales,
        locale: const Locale('ar'),
        home: WalletBalanceCard(balance: 15000, currency: 'SYP'),
      ),
    );

    expect(find.text('١٥٬٠٠٠ ل.س'), findsOneWidget);
  });
}
```

### Build and Performance
- `flutter build apk --split-per-abi` for production (armeabi-v7a, arm64-v8a).
- Minimum SDK: 21 (Android 5.0), target: 33+.
- Shrinking enabled: `minifyEnabled true`, `shrinkResources true`.
- Lint warnings treated as errors in CI.
- No `print()` statements in production code. Use `Logger` package with configurable levels.
