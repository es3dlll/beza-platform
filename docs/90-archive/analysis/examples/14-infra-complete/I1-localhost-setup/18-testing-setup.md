# 18 - إعداد بيئة الاختبارات (Testing Setup)

## إعداد PHPUnit/Pest

```bash
cd backend-laravel
php artisan test
php artisan test --coverage
php artisan test --filter=TransferTest
```

## إعداد بيئة الاختبار

```env
# .env.testing
APP_ENV=testing
DB_CONNECTION=mysql
DB_DATABASE=beza_testing
```

## إعداد Flutter Tests

```bash
cd mobile-app
flutter test
flutter test --coverage
flutter test --integration
```

## إعداد K6

```bash
# تثبيت k6 من https://k6.io/docs/getting-started/installation/
cd load-tests
k6 run load-test.js
k6 run --vus 100 --duration 30s stress-test.js
```

## اختبارات ما بعد الإعداد

```bash
# 1. التحقق من API
curl http://localhost:8000/api/ping

# 2. تسجيل مستخدم
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","phone":"963900000001","password":"123456","pin":"1234"}'

# 3. تحقق من الرصيد
curl http://localhost:8000/api/v1/wallet -H "Authorization: Bearer TOKEN"
```
