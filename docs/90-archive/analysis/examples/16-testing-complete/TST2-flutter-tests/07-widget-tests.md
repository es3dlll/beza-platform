# 07 - اختبارات الـ Widgets (Widget Tests)

## Login Screen Test

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:beza_mobile/features/auth/presentation/screens/login_screen.dart';
import 'package:bloc_test/bloc_test.dart';
import 'package:mockito/mockito.dart';

void main() {
  testWidgets('Login screen shows form fields', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: LoginScreen(),
      ),
    );

    // التحقق من وجود حقول الإدخال
    expect(find.byKey(Key('phone_field')), findsOneWidget);
    expect(find.byKey(Key('password_field')), findsOneWidget);
    expect(find.text('دخول'), findsOneWidget);
    expect(find.text('ليس لديك حساب؟ سجل الآن'), findsOneWidget);
  });

  testWidgets('Login button disabled when fields empty', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: LoginScreen(),
      ),
    );

    final loginButton = tester.widget<ElevatedButton>(find.text('دخول'));
    expect(loginButton.onPressed, isNull); // معطل
  });

  testWidgets('Shows error for invalid phone', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: LoginScreen(),
      ),
    );

    await tester.enterText(find.byKey(Key('phone_field')), '123');
    await tester.enterText(find.byKey(Key('password_field')), 'password');
    await tester.tap(find.text('دخول'));
    await tester.pumpAndSettle();

    expect(find.text('رقم الهاتف غير صحيح'), findsOneWidget);
  });
}
```

## Transfer Form Test

```dart
testWidgets('Transfer form completes successfully', (tester) async {
  await tester.pumpWidget(
    MaterialApp(
      home: TransferScreen(),
    ),
  );

  // إدخال البيانات
  await tester.enterText(find.byKey(Key('phone_field')), '963900000002');
  await tester.enterText(find.byKey(Key('amount_field')), '100');
  await tester.enterText(find.byKey(Key('pin_field')), '1234');

  await tester.tap(find.text('تحويل'));
  await tester.pumpAndSettle();

  // التحقق من النتيجة
  expect(find.text('تم التحويل بنجاح'), findsOneWidget);
});

testWidgets('Shows error for insufficient balance', (tester) async {
  await tester.pumpWidget(
    MaterialApp(
      home: TransferScreen(),
    ),
  );

  await tester.enterText(find.byKey(Key('phone_field')), '963900000002');
  await tester.enterText(find.byKey(Key('amount_field')), '999999');
  await tester.enterText(find.byKey(Key('pin_field')), '1234');

  await tester.tap(find.text('تحويل'));
  await tester.pumpAndSettle();

  expect(find.text('رصيد غير كافٍ'), findsOneWidget);
});
```
