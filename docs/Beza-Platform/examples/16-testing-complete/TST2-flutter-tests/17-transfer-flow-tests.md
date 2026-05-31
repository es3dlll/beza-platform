# 17 - اختبارات تدفق التحويل (Transfer Flow Tests)

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:beza_mobile/features/transfer/presentation/screens/transfer_screen.dart';

void main() {
  group('Transfer Screen Tests', () {
    testWidgets('shows transfer form elements', (tester) async {
      await tester.pumpWidget(
        MaterialApp(home: TransferScreen()),
      );

      expect(find.byKey(Key('phone_field')), findsOneWidget);
      expect(find.byKey(Key('amount_field')), findsOneWidget);
      expect(find.byKey(Key('currency_dropdown')), findsOneWidget);
      expect(find.byKey(Key('pin_field')), findsOneWidget);
      expect(find.text('تحويل'), findsOneWidget);
    });

    testWidgets('currency dropdown shows options', (tester) async {
      await tester.pumpWidget(
        MaterialApp(home: TransferScreen()),
      );

      await tester.tap(find.byKey(Key('currency_dropdown')));
      await tester.pumpAndSettle();

      expect(find.text('USD \$'), findsOneWidget);
      expect(find.text('SYP ل.س'), findsOneWidget);
    });

    testWidgets('shows validation errors for empty form', (tester) async {
      await tester.pumpWidget(
        MaterialApp(home: TransferScreen()),
      );

      await tester.tap(find.text('تحويل'));
      await tester.pumpAndSettle();

      expect(find.text('رقم الهاتف مطلوب'), findsOneWidget);
      expect(find.text('المبلغ مطلوب'), findsOneWidget);
      expect(find.text('PIN مطلوب'), findsOneWidget);
    });

    testWidgets('enables submit button when form is valid', (tester) async {
      await tester.pumpWidget(
        MaterialApp(home: TransferScreen()),
      );

      await tester.enterText(find.byKey(Key('phone_field')), '963900000002');
      await tester.enterText(find.byKey(Key('amount_field')), '100');
      await tester.enterText(find.byKey(Key('pin_field')), '1234');

      final submitButton = tester.widget<ElevatedButton>(find.text('تحويل'));
      expect(submitButton.onPressed, isNotNull);
    });
  });
}
```
