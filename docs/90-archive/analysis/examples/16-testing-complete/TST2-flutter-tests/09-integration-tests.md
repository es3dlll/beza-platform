# 09 - اختبارات التكامل (Integration Tests)

## Login Flow

```dart
// integration_test/login_flow_test.dart
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:beza_mobile/main.dart' as app;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  group('Login Flow', () {
    testWidgets('full login flow', (tester) async {
      app.main();
      await tester.pumpAndSettle();

      // 1. شاشة الترحيب
      expect(find.text('مرحباً بك في Beza'), findsOneWidget);
      await tester.tap(find.text('تسجيل الدخول'));
      await tester.pumpAndSettle();

      // 2. إدخال بيانات الدخول
      await tester.enterText(find.byKey(Key('phone_field')), '963900000001');
      await tester.enterText(find.byKey(Key('password_field')), 'password');
      await tester.tap(find.text('دخول'));
      await tester.pumpAndSettle();

      // 3. التحقق من الوصول للرئيسية
      expect(find.text('الرئيسية'), findsOneWidget);
      expect(find.text('المحفظة'), findsOneWidget);
    });
  });
}
```

## Transfer Flow

```dart
// integration_test/transfer_flow_test.dart
testWidgets('send money to another user', (tester) async {
  app.main();
  await tester.pumpAndSettle();

  // تسجيل الدخول أولاً
  await tester.enterText(find.byKey(Key('phone_field')), '963900000001');
  await tester.enterText(find.byKey(Key('password_field')), 'password');
  await tester.tap(find.text('دخول'));
  await tester.pumpAndSettle();

  // الانتقال للتحويل
  await tester.tap(find.text('تحويل'));
  await tester.pumpAndSettle();

  // إدخال بيانات التحويل
  await tester.enterText(find.byKey(Key('phone_field')), '963900000002');
  await tester.enterText(find.byKey(Key('amount_field')), '50');
  await tester.enterText(find.byKey(Key('pin_field')), '1234');

  await tester.tap(find.text('تحويل'));
  await tester.pumpAndSettle();

  // التحقق من النجاح
  expect(find.text('تم التحويل بنجاح'), findsOneWidget);
  expect(find.text('الرصيد الجديد: \$950.00'), findsOneWidget);
});
```
