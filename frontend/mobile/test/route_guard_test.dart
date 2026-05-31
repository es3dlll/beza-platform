import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'package:beza_mobile/services/auth_service.dart';
import 'package:beza_mobile/widgets/route_guard.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('RouteGuard - routing logic', () {
    setUp(() {
      FlutterSecureStorage.setMockInitialValues({});
    });

    testWidgets('shows loading then redirects to login when not authenticated',
        (tester) async {
      final authService = AuthService(baseUrl: 'http://localhost:8000/api');

      await tester.pumpWidget(
        MaterialApp(
          home: RouteGuard(authService: authService),
        ),
      );

      // Initially shows CircularProgressIndicator
      expect(find.byType(CircularProgressIndicator), findsOneWidget);

      // Wait for auth check to complete (no token = redirect to login)
      await tester.pump(const Duration(seconds: 2));
      await tester.pump();

      // After check, should show login screen
      expect(find.text('بيزا'), findsOneWidget);
      expect(find.text('تسجيل الدخول'), findsOneWidget);
    });
  });
}
