# 18 - جميع الاختبارات (Testing Complete)

## تشغيل جميع الاختبارات

```bash
# تشغيل كل شيء
php artisan test --verbose

# تشغيل مع توازي (أسرع)
php artisan test --parallel

# تشغيل مع تغطية
php artisan test --coverage --min=80
```

## قائمة جميع الاختبارات

```bash
# اختبارات المصادقة
php artisan test --filter=AuthTest

# اختبارات المحفظة
php artisan test --filter=WalletTest

# اختبارات التحويل
php artisan test --filter=TransferTest

# اختبارات التاجر
php artisan test --filter=MerchantTest

# اختبارات الوكيل
php artisan test --filter=AgentTest

# اختبارات الصفقات
php artisan test --filter=DealTest

# اختبارات البطاقات
php artisan test --filter=CardTest

# اختبارات KYC
php artisan test --filter=KycTest

# اختبارات الأدمن
php artisan test --filter=AdminTest

# اختبارات الإشعارات
php artisan test --filter=NotificationTest

# اختبارات 2FA
php artisan test --filter=TwoFactorTest

# اختبارات الاحتيال
php artisan test --filter=FraudDetectionTest

# اختبارات الأداء
php artisan test --filter=PerformanceTest

# اختبارات Pest
./vendor/bin/pest
```

## النتيجة المتوقعة

```
  PASS  Tests\Feature\AuthTest
  ✓ user can register
  ✓ user cannot register with existing phone
  ✓ user can login
  ✓ suspended user cannot login
  ✓ user can logout

  PASS  Tests\Feature\TransferTest
  ✓ completes successful transfer
  ✓ fails for self transfer
  ✓ fails with insufficient balance
  ✓ fails with wrong pin
  ✓ requires authentication
  ✓ validates required fields

  Tests:  82 passed
  Coverage: 92.5%
```
