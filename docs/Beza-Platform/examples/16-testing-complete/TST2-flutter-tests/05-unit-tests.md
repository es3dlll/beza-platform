# 05 - اختبارات الوحدة (Unit Tests)

## Validators Test

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:beza_mobile/utils/validators.dart';

void main() {
  group('Phone Validator', () {
    test('accepts valid phone number', () {
      expect(Validators.isValidPhone('963900000001'), true);
    });

    test('rejects short phone', () {
      expect(Validators.isValidPhone('123'), false);
    });

    test('rejects empty phone', () {
      expect(Validators.isValidPhone(''), false);
    });
  });

  group('Amount Validator', () {
    test('accepts valid amount', () {
      expect(Validators.isValidAmount('100'), true);
    });

    test('accepts decimal amount', () {
      expect(Validators.isValidAmount('100.50'), true);
    });

    test('rejects zero', () {
      expect(Validators.isValidAmount('0'), false);
    });

    test('rejects negative', () {
      expect(Validators.isValidAmount('-100'), false);
    });

    test('rejects empty', () {
      expect(Validators.isValidAmount(''), false);
    });
  });

  group('Pin Validator', () {
    test('accepts 4-digit pin', () {
      expect(Validators.isValidPin('1234'), true);
    });

    test('rejects 3-digit pin', () {
      expect(Validators.isValidPin('123'), false);
    });

    test('rejects non-numeric pin', () {
      expect(Validators.isValidPin('abcd'), false);
    });
  });
}
```

## Formatters Test

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:beza_mobile/utils/formatters.dart';

void main() {
  group('Currency Formatter', () {
    test('formats USD amount', () {
      expect(Formatters.formatCurrency(100.00, 'USD'), '\$100.00');
    });

    test('formats SYP amount', () {
      expect(Formatters.formatCurrency(1000.00, 'SYP'), 'ل.س 1,000.00');
    });

    test('formats zero', () {
      expect(Formatters.formatCurrency(0, 'USD'), '\$0.00');
    });
  });

  group('Phone Formatter', () {
    test('formats phone number', () {
      expect(Formatters.formatPhone('963900000001'), '963 900 000 001');
    });
  });

  group('Date Formatter', () {
    test('formats date', () {
      final date = DateTime(2026, 5, 27, 14, 30);
      expect(Formatters.formatDate(date), '2026-05-27');
    });
  });
}
```
