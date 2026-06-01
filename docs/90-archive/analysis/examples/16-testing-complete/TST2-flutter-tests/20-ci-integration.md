# 20 - التكامل مع CI (CI Integration)

## GitHub Actions

```yaml
# .github/workflows/flutter_tests.yml
name: Flutter Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup Flutter
        uses: subosito/flutter-action@v2
        with:
          flutter-version: '3.x'
          channel: 'stable'

      - name: Install Dependencies
        run: flutter pub get
        working-directory: mobile-app

      - name: Analyze Code
        run: flutter analyze
        working-directory: mobile-app

      - name: Run Unit & Widget Tests
        run: flutter test --coverage
        working-directory: mobile-app

      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          file: mobile-app/coverage/lcov.info
          flags: flutter

      - name: Build APK
        run: flutter build apk --debug
        working-directory: mobile-app
```

## GitLab CI

```yaml
# .gitlab-ci.yml
flutter-test:
  image: cirrusci/flutter:3.x
  stage: test
  script:
    - cd mobile-app
    - flutter pub get
    - flutter analyze
    - flutter test --coverage
  artifacts:
    paths:
      - mobile-app/coverage/
```

## فشل CI

```bash
# يفشل CI إذا:
# 1. أي اختبار يفشل
# 2. التحليل (analyze) يجد أخطاء
# 3. التغطية أقل من 70%

flutter test --coverage
# إذا فشل أي اختبار → exit code 1 → CI fails
```
