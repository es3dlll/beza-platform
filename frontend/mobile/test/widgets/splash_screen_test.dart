import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:mockito/mockito.dart';
import 'package:beza_platform/features/splash/screens/splash_screen.dart';
import 'package:beza_platform/features/auth/providers/auth_provider.dart';

import '../helpers/test_helpers.dart';
import '../helpers/mocks.mocks.dart';

void main() {
  testWidgets('splash screen renders app name and subtitle', (tester) async {
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
            initialLocation: '/splash',
            routes: [
              GoRoute(
                path: '/splash',
                builder: (_, _) => const SplashScreen(),
              ),
              GoRoute(
                path: '/welcome',
                builder: (_, _) => const Scaffold(
                  body: Center(child: Text('Welcome Page')),
                ),
              ),
            ],
          ),
        ),
      ),
    );

    await tester.pump();

    expect(find.text('بزة'), findsOneWidget);
    expect(find.text('BEZA'), findsOneWidget);
    expect(find.text('منصتك المالية الشاملة في سورية'), findsOneWidget);

    await tester.pump(const Duration(seconds: 1));
    await tester.pump(const Duration(seconds: 2));

    expect(find.text('بزة'), findsOneWidget);
  });
}
