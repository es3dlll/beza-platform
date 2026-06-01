# 18 - تشغيل جميع الاختبارات (Testing Complete)

## تشغيل كل شيء

```bash
# جميع اختبارات Flutter
flutter test

# اختبارات الوحدة فقط
flutter test test/unit/

# اختبارات الـ Widget فقط
flutter test test/widget/

# اختبارات الـ BLoC فقط
flutter test test/bloc/

# اختبارات التكامل
flutter test test/integration/

# مع تغطية
flutter test --coverage

# على platform محدد
flutter test -d chrome
flutter test -d android
```

## النتيجة المتوقعة

```
00:00 +42: All tests passed!

Summary:
  Unit Tests:    15 passed
  Widget Tests:  18 passed
  BLoC Tests:    6 passed
  Integration:   3 passed
  Total:         42 passed
  Coverage:      85.3%
```
