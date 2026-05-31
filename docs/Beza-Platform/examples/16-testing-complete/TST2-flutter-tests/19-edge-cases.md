# 19 - حالات الحافة (Edge Cases)

## 1. RTL والعربية

```dart
testWidgets('Arabic text displays correctly', (tester) async {
  await tester.pumpWidget(
    MaterialApp(
      locale: Locale('ar'),
      home: TransferScreen(),
    ),
  );

  // التحقق من النصوص العربية
  expect(find.text('تحويل'), findsOneWidget);
  expect(find.text('رقم الهاتف'), findsOneWidget);
  expect(find.text('المبلغ'), findsOneWidget);
});
```

## 2. أجهزة مختلفة (Screen Sizes)

```dart
testWidgets('adapts to small screen', (tester) async {
  tester.view.setPhysicalSize(Size(360, 640)); // شاشة صغيرة
  await tester.pumpWidget(
    MaterialApp(home: TransferScreen()),
  );

  // التحقق من أن كل العناصر مرئية
  expect(find.byKey(Key('phone_field')), findsOneWidget);
  expect(find.byKey(Key('amount_field')), findsOneWidget);
  expect(find.text('تحويل'), findsOneWidget);

  tester.view.resetPhysicalSize();
});
```

## 3. لوحة المفاتيح (Keyboard)

```dart
testWidgets('keyboard appears for amount field', (tester) async {
  await tester.pumpWidget(
    MaterialApp(home: TransferScreen()),
  );

  await tester.tap(find.byKey(Key('amount_field')));
  await tester.pumpAndSettle();

  // التحقق من أن لوحة الأرقام ظهرت
  expect(tester.testTextInput.isVisible, isTrue);
});
```

## 4. انقطاع الشبكة

```dart
testWidgets('handles network error gracefully', (tester) async {
  when(mockApiClient.post('/transfer', any))
    .thenThrow(SocketException('No connection'));

  await tester.pumpWidget(
    MaterialApp(home: TransferScreen()),
  );

  await tester.enterText(find.byKey(Key('phone_field')), '963900000002');
  await tester.enterText(find.byKey(Key('amount_field')), '100');
  await tester.enterText(find.byKey(Key('pin_field')), '1234');
  await tester.tap(find.text('تحويل'));
  await tester.pumpAndSettle();

  expect(find.text('خطأ في الاتصال بالشبكة'), findsOneWidget);
});
```
