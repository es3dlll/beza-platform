# 16 - اختبارات تدفق الدخول (Login Flow Tests)

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:beza_mobile/features/auth/presentation/screens/login_screen.dart';

void main() {
  group('Login Screen UI Tests', () {
    testWidgets('shows all required elements', (tester) async {
      await tester.pumpWidget(
        MaterialApp(home: LoginScreen()),
      );

      expect(find.byType(TextFormField), findsNWidgets(2));
      expect(find.text('دخول'), findsOneWidget);
      expect(find.text('ليس لديك حساب؟ سجل الآن'), findsOneWidget);
      expect(find.byType(GestureDetector), findsWidgets);
    });

    testWidgets('validates empty fields on submit', (tester) async {
      await tester.pumpWidget(
        MaterialApp(home: LoginScreen()),
      );

      await tester.tap(find.text('دخول'));
      await tester.pumpAndSettle();

      expect(find.text('رقم الهاتف مطلوب'), findsOneWidget);
      expect(find.text('كلمة المرور مطلوبة'), findsOneWidget);
    });

    testWidgets('navigates to register on text tap', (tester) async {
      await tester.pumpWidget(
        MaterialApp(
          initialRoute: '/login',
          routes: {
            '/login': (context) => LoginScreen(),
            '/register': (context) => Scaffold(body: Text('تسجيل جديد')),
          },
        ),
      );

      await tester.tap(find.text('ليس لديك حساب؟ سجل الآن'));
      await tester.pumpAndSettle();

      expect(find.text('تسجيل جديد'), findsOneWidget);
    });
  });
}
```
