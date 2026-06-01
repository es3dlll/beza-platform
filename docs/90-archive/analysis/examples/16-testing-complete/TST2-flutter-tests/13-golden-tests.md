# 13 - اختبارات الـ Golden (Golden Tests)

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:beza_mobile/features/transfer/presentation/widgets/transfer_form.dart';

void main() {
  testWidgets('TransferForm golden test', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: ThemeData(
          primarySwatch: Colors.blue,
          fontFamily: 'Cairo',
        ),
        home: Scaffold(
          body: TransferForm(
            onSubmit: (phone, amount, currency, pin) {},
          ),
        ),
      ),
    );

    await expectLater(
      find.byType(TransferForm),
      matchesGoldenFile('goldens/transfer_form.png'),
    );
  });
}
```

## إنشاء/تحديث Golden Files

```bash
# إنشاء ملفات golden
flutter test --update-goldens

# مقارنة
flutter test
```
