# 12 - تقارير التغطية (Coverage Reporting)

```bash
# تشغيل مع تغطية
flutter test --coverage

# تحويل التغطية لتقرير HTML
genhtml coverage/lcov.info -o coverage/html

# فتح التقرير
start coverage/html/index.html

# عرض في الطرفية
lcov --list coverage/lcov.info
```

## تكوين التغطية

```yaml
# pubspec.yaml
dev_dependencies:
  flutter_lints: ^3.0.1

# تجاهل المجلدات غير المراد تغطيتها
# coverage/lcov.info سيحتوي فقط على ملفات lib/
```

## التكامل مع Codecov

```yaml
# .github/workflows/flutter_tests.yml
- name: Run tests with coverage
  run: flutter test --coverage

- name: Upload coverage
  uses: codecov/codecov-action@v3
  with:
    file: coverage/lcov.info
    flags: flutter
```
