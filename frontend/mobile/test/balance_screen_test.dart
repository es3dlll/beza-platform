import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'package:beza_mobile/core/money.dart';
import 'package:beza_mobile/screens/login_screen.dart';
import 'package:beza_mobile/services/auth_service.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('Money display in UI', () {
    setUp(() {
      FlutterSecureStorage.setMockInitialValues({});
    });

    test('Money formats correctly for balance display', () {
      final money = Money.fromFils(1234567);
      expect(money.format(), contains('ل.س'));
      expect(money.fils, equals(1234567));
    });

    testWidgets('login screen shows Arabic title and heading', (tester) async {
      final authService = AuthService(baseUrl: 'http://localhost:8000/api');
      await tester.pumpWidget(
        MaterialApp(
          home: LoginScreen(authService: authService),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('بيزا'), findsOneWidget);
      expect(find.text('تسجيل الدخول'), findsOneWidget);
    });

    testWidgets('login screen has register link', (tester) async {
      final authService = AuthService(baseUrl: 'http://localhost:8000/api');
      await tester.pumpWidget(
        MaterialApp(
          home: LoginScreen(authService: authService),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('ليس لديك حساب؟ سجل الآن'), findsOneWidget);
    });
  });
}
