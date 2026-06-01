# TST2 - اختبارات Flutter

## الوصف
اختبارات واجهة المستخدم لجوال Flutter.

## Widget Tests

### Login Flow
```dart
testWidgets('Login flow', (tester) async {
  await tester.pumpWidget(MyApp());
  await tester.enterText(find.byKey(Key('phone')), '0991234567');
  await tester.enterText(find.byKey(Key('password')), 'password');
  await tester.tap(find.text('دخول'));
  await tester.pumpAndSettle();
  expect(find.text('الرصيد'), findsOneWidget);
});
```

### قائمة الاختبارات
- Register Screen
  - إدخال بيانات صحيحة ← زر التسجيل مفعل
  - إدخال رقم ناقص ← رسالة خطأ

- Transfer Screen
  - إدخال مبلغ + PIN ← تحويل ناجح
  - رصيد غير كاف ← رسالة خطأ

- Agent Dashboard
  - عرض الأرصدة ← 4 أرقام
  - زر سحب نقدي ← يفتح scanner

- Deals Screen
  - عرض الصفقات المتاحة ← قائمة
  - المشاركة في صفقة ← تأكيد

## Integration Tests
- تسجيل ← تسجيل دخول ← تحويل ← تسجيل خروج
- تسجيل وكيل ← إيداع نقدي ← التحقق من الرصيد

## تشغيل الاختبارات
```bash
flutter test
flutter test --integration
```
