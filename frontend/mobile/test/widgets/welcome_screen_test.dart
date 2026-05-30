import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:mockito/mockito.dart';
import 'package:beza_platform/features/auth/screens/welcome_screen.dart';
import 'package:beza_platform/features/auth/providers/auth_provider.dart';

import '../helpers/test_helpers.dart';
import '../helpers/mocks.mocks.dart';

void main() {
  testWidgets('welcome screen displays title and buttons', (tester) async {
    final mockService = MockAuthService();
    final notifier = AuthNotifier(mockService);

    when(mockService.getToken()).thenAnswer((_) async => null);

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authProvider.overrideWith((ref) => notifier),
        ],
        child: MaterialApp.router(
          routerConfig: GoRouter(
            initialLocation: '/welcome',
            routes: [
              GoRoute(
                path: '/welcome',
                builder: (_, _) => const WelcomeScreen(),
              ),
              GoRoute(
                path: '/phone',
                builder: (_, _) => const Scaffold(
                  body: Center(child: Text('Phone Entry')),
                ),
              ),
              GoRoute(
                path: '/pin/entry',
                builder: (_, _) => const Scaffold(
                  body: Center(child: Text('PIN Entry')),
                ),
              ),
            ],
          ),
        ),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 1100));

    expect(find.text('مرحباً بك في بزة'), findsOneWidget);
    expect(find.text('تسجيل الدخول'), findsOneWidget);
    expect(find.textContaining('ليس لديك حساب؟'), findsOneWidget);
    expect(find.textContaining('منصتك المالية الشاملة في سورية'), findsOneWidget);
    expect(find.text('آمن ومشفر'), findsOneWidget);
    expect(find.text('فوري وسريع'), findsOneWidget);
    expect(find.text('دعم متواصل'), findsOneWidget);
    expect(find.text('BEZA'), findsOneWidget);
  });

  testWidgets('welcome screen create account button navigates to registration', (tester) async {
    final mockService = MockAuthService();
    final notifier = AuthNotifier(mockService);

    when(mockService.getToken()).thenAnswer((_) async => null);

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authProvider.overrideWith((ref) => notifier),
        ],
        child: MaterialApp.router(
          routerConfig: GoRouter(
            initialLocation: '/welcome',
            routes: [
              GoRoute(
                path: '/welcome',
                builder: (_, _) => const WelcomeScreen(),
              ),
              GoRoute(
                path: '/register',
                builder: (_, _) => const Scaffold(
                  body: Center(child: Text('Register Page')),
                ),
              ),
              GoRoute(
                path: '/pin/entry',
                builder: (_, _) => const Scaffold(
                  body: Center(child: Text('PIN Entry Page')),
                ),
              ),
            ],
          ),
        ),
      ),
    );

    await tester.pump();
    await tester.pump(const Duration(milliseconds: 1100));

    await tester.tap(find.textContaining('إنشاء حساب جديد'));
    await tester.pumpAndSettle();

    expect(find.text('Register Page'), findsOneWidget);
  });
}
