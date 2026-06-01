# 04 - إعداد بيئة الاختبار (Test Setup)

## main test preparation

```dart
// test/helpers/test_setup.dart
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/annotations.dart';
import 'package:mockito/mockito.dart';

@GenerateMocks([ApiClient, AuthService, TransferService])
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
}
```

## توليد Mocks

```bash
# توليد ملفات mock تلقائياً
flutter pub run build_runner build

# تنظيف الملفات القديمة
flutter pub run build_runner clean
```

## إعداد Mock للاختبارات

```dart
// test/unit/services/api_client_test.dart
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';

class MockDio extends Mock implements Dio {}

void main() {
  late MockDio mockDio;
  late ApiClient apiClient;

  setUp(() {
    mockDio = MockDio();
    apiClient = ApiClient(dio: mockDio);
  });

  tearDown(() {
    // تنظيف بعد كل اختبار
  });
}
```
