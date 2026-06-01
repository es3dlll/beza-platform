# 01 - استراتيجية اختبارات Flutter

## أنواع الاختبارات

| النوع | الأدوات | الوصف |
|-------|---------|-------|
| Unit Tests | flutter_test | اختبار الدوال والكلاسات |
| Widget Tests | flutter_test | اختبار الـ Widgets |
| Integration Tests | integration_test | اختبار التكامل الكامل |

## هرم الاختبارات

```
          ╱╲
         ╱  ╲
        ╱ Int ╲
       ╱  Te   ╲
      ╱─────────╲
     ╱   Widget  ╲
    ╱    Tests    ╲
   ╱────────────────╲
  ╱    Unit Tests    ╲
 ╱  (BLoC, Services,  ╲
╱   Repositories, Utils)╲
──────────────────────────
```

## الإعدادات في pubspec.yaml

```yaml
dev_dependencies:
  flutter_test:
    sdk: flutter
  integration_test:
    sdk: flutter
  mockito: ^5.4.3
  build_runner: ^2.4.6
  bloc_test: ^9.1.4
  golden_toolkit: ^0.15.0
```
