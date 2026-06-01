# 16 - تطبيق Flutter (Flutter Implementation) - فهرس - تشغيل البيئة المحلية (Localhost Setup)

## pubspec.yaml

```yaml
name: beza_mobile
description: Beza Platform Mobile App
publish_to: 'none'
version: 1.0.0+1

environment:
  sdk: '>=3.0.0 <4.0.0'

dependencies:
  flutter:
    sdk: flutter
  dio: ^5.3.0
  flutter_bloc: ^8.1.3
  flutter_secure_storage: ^9.0.0
  firebase_messaging: ^14.7.0
  firebase_core: ^2.24.0
  qr_flutter: ^4.1.0
  mobile_scanner: ^3.5.0

dev_dependencies:
  flutter_test:
    sdk: flutter
  mockito: ^5.4.3
  build_runner: ^2.4.6
```

## إعداد البيئة

```dart
class Environment {
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );
}
```

## أوامر البناء

```bash
flutter run                                       # تطوير
flutter build apk --release                       # APK
flutter build appbundle --release                 # Google Play
flutter run --dart-define=API_BASE_URL=http://192.168.1.100:8000/api/v1
```
