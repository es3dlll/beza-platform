# 14 - اختبارات الأداء (Performance Tests)

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:beza_mobile/utils/validators.dart';

void main() {
  group('Performance Tests', () {
    test('validate phone number under 1ms', () {
      final stopwatch = Stopwatch()..start();

      for (int i = 0; i < 1000; i++) {
        Validators.isValidPhone('963900000001');
      }

      stopwatch.stop();
      final avgMicroseconds = stopwatch.elapsedMicroseconds / 1000;

      expect(avgMicroseconds, lessThan(1000)); // أقل من 1ms لكل عملية
    });

    test('format currency under 1ms', () {
      final stopwatch = Stopwatch()..start();

      for (int i = 0; i < 1000; i++) {
        Formatters.formatCurrency(1000.50, 'USD');
      }

      stopwatch.stop();
      final avgMicroseconds = stopwatch.elapsedMicroseconds / 1000;

      expect(avgMicroseconds, lessThan(1000));
    });
  });
}
```

## Profiling

```bash
# تشغيل مع profiling
flutter test --profile

# تشغيل مع مراقبة الأداء
flutter test --trace-startup --profile
```
