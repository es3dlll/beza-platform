import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/auth/providers/auth_provider.dart';
import '../features/notifications/services/fcm_service.dart';
import '../features/splash/screens/splash_screen.dart';
import '../features/auth/screens/welcome_screen.dart';
import '../features/auth/screens/phone_entry_screen.dart';
import '../features/auth/screens/otp_screen.dart';
import '../features/auth/screens/pin_create_screen.dart';
import '../features/auth/screens/pin_entry_screen.dart';
import '../features/auth/screens/biometric_screen.dart';
import '../features/auth/screens/registration_screen.dart';
import '../features/auth/screens/login_screen.dart';
import '../features/auth/screens/security_setup_screen.dart';
import '../features/settings/screens/settings_screen.dart';
import '../features/settings/screens/phone_verification_screen.dart';
import '../features/settings/screens/about_screen.dart';
import '../features/settings/screens/change_pin_screen.dart';
import '../features/shell/shell_screen.dart';
import '../features/home/screens/home_screen.dart';
import '../features/wallet/screens/wallet_screen.dart';
import '../features/transactions/screens/transactions_screen.dart';
import '../features/more/screens/more_screen.dart';
import '../features/profile/screens/profile_screen.dart';
import '../features/bills/screens/bills_screen.dart';
import '../features/cards/screens/cards_screen.dart';
import '../features/agent/screens/agent_screen.dart';
import '../features/financing/screens/financing_screen.dart';
import '../features/education/screens/education_screen.dart';
import '../features/humanitarian/screens/humanitarian_screen.dart';
import '../features/loyalty/screens/loyalty_screen.dart';
import '../features/merchant/screens/merchant_screen.dart';
import '../features/fx/screens/fx_screen.dart';
import '../features/remittance/screens/remittance_screen.dart';
import '../features/payroll/screens/payroll_screen.dart';
import '../features/notifications/screens/notifications_screen.dart';
import '../features/savings/screens/savings_screen.dart';
import '../features/gov_collections/screens/gov_collections_screen.dart';
import '../features/open_finance/screens/open_finance_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final goRouter = GoRouter(
    initialLocation: '/splash',
    debugLogDiagnostics: false,
    redirect: (context, state) {
      final authState = ref.read(authProvider);
      if (state.matchedLocation == '/splash') return null;

      final loggedIn = authState.isAuthenticated;
      final setupComplete = authState.isSecuritySetupComplete;
      final onAuth = state.matchedLocation.startsWith('/welcome') ||
          state.matchedLocation.startsWith('/phone') ||
          state.matchedLocation.startsWith('/otp') ||
          state.matchedLocation.startsWith('/pin') ||
          state.matchedLocation.startsWith('/biometric') ||
          state.matchedLocation.startsWith('/register') ||
          state.matchedLocation.startsWith('/login') ||
          state.matchedLocation.startsWith('/security-setup');

      if (!loggedIn && !onAuth) return '/welcome';
      if (loggedIn && !setupComplete && state.matchedLocation != '/security-setup') return '/security-setup';
      if (loggedIn && setupComplete && onAuth) return '/';
      return null;
    },
    routes: [
      GoRoute(path: '/splash', builder: (_, _) => const SplashScreen()),
      GoRoute(path: '/welcome', builder: (_, _) => const WelcomeScreen()),
      GoRoute(path: '/phone', builder: (_, _) => const PhoneEntryScreen()),
      GoRoute(path: '/otp', builder: (_, _) => const OtpScreen()),
      GoRoute(path: '/pin/create', builder: (_, _) => const PinCreateScreen()),
      GoRoute(path: '/pin/entry', builder: (_, _) => const PinEntryScreen()),
      GoRoute(path: '/biometric', builder: (_, _) => const BiometricScreen()),
      GoRoute(path: '/register', builder: (_, _) => const RegistrationScreen()),
      GoRoute(path: '/login', builder: (_, _) => const LoginScreen()),
      GoRoute(path: '/security-setup', builder: (_, _) => const SecuritySetupScreen()),
      GoRoute(path: '/settings', builder: (_, _) => const SettingsScreen()),
      GoRoute(path: '/settings/verify-phone', builder: (_, _) => const PhoneVerificationScreen()),
      GoRoute(path: '/settings/change-pin', builder: (_, _) => const ChangePinScreen()),
      GoRoute(path: '/settings/about', builder: (_, _) => const AboutScreen()),
      ShellRoute(
        builder: (_, _, child) => ShellScreen(child: child),
        routes: [
          GoRoute(path: '/', builder: (_, _) => const HomeScreen()),
          GoRoute(path: '/wallet', builder: (_, _) => const WalletScreen()),
          GoRoute(path: '/transactions', builder: (_, _) => const TransactionsScreen()),
          GoRoute(path: '/more', builder: (_, _) => const MoreScreen()),
          GoRoute(path: '/profile', builder: (_, _) => const ProfileScreen()),
          GoRoute(path: '/bills', builder: (_, _) => const BillsScreen()),
          GoRoute(path: '/cards', builder: (_, _) => const CardsScreen()),
          GoRoute(path: '/agent', builder: (_, _) => const AgentScreen()),
          GoRoute(path: '/financing', builder: (_, _) => const FinancingScreen()),
          GoRoute(path: '/education', builder: (_, _) => const EducationScreen()),
          GoRoute(path: '/humanitarian', builder: (_, _) => const HumanitarianScreen()),
          GoRoute(path: '/loyalty', builder: (_, _) => const LoyaltyScreen()),
          GoRoute(path: '/merchant', builder: (_, _) => const MerchantScreen()),
          GoRoute(path: '/fx', builder: (_, _) => const FxScreen()),
          GoRoute(path: '/remittance', builder: (_, _) => const RemittanceScreen()),
          GoRoute(path: '/payroll', builder: (_, _) => const PayrollScreen()),
          GoRoute(path: '/notifications', builder: (_, _) => const NotificationsScreen()),
          GoRoute(path: '/savings', builder: (_, _) => const SavingsScreen()),
          GoRoute(path: '/gov-collections', builder: (_, _) => const GovCollectionsScreen()),
          GoRoute(path: '/open-finance', builder: (_, _) => const OpenFinanceScreen()),
        ],
      ),
    ],
  );

  ref.listen(authProvider, (_, _) => goRouter.refresh());

  FcmService.setRouter(goRouter);

  return goRouter;
});
