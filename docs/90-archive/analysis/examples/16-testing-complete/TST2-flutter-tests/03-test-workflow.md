# 03 - تدفق تشغيل الاختبارات (Test Workflow)

## تشغيل الاختبارات

```bash
# تشغيل جميع الاختبارات
flutter test

# تشغيل مع تغطية
flutter test --coverage

# تشغيل ملف معين
flutter test test/unit/services/api_client_test.dart

# تشغيل اختبارات الـ Widget
flutter test test/widget/

# تشغيل اختبارات التكامل
flutter test test/integration/

# تشغيل مع اسم
flutter test --name "login"

# تشغيل مع المنصة
flutter test --platform chrome
```

## إعداد التهيئة

```dart
// test/helpers/test_helpers.dart
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';

// تهيئة common قبل كل الاختبارات
TestWidgetsFlutterBinding.ensureInitialized();

// مصادقة وهمية
class MockAuthService extends Mock implements AuthService {}
class MockTransferRepository extends Mock implements TransferRepository {}
class MockApiClient extends Mock implements ApiClient {}
```
