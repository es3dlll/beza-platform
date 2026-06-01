# 01 - استراتيجية الاختبارات (Testing Strategy)

## أنواع الاختبارات

| النوع | الأدوات | التغطية المستهدفة |
|-------|---------|-------------------|
| Unit Tests | PHPUnit | 90%+ للـ Services |
| Feature Tests | PHPUnit | 90%+ للـ Controllers |
| Pest Tests | Pest PHP | 80%+ للـ API endpoints |

## هرم الاختبارات

```
          ╱╲
         ╱  ╲
        ╱ UI ╲
       ╱ Tests ╲
      ╱─────────╲
     ╱ Feature   ╲
    ╱   Tests     ╲
   ╱────────────────╲
  ╱   Unit Tests     ╲
 ╱   (Services,       ╲
╱    Models, Helpers)  ╲
──────────────────────────
```

## الملفات المطلوبة

```bash
# إنشاء ملفات الاختبار
php artisan make:test AuthTest
php artisan make:test WalletTest
php artisan make:test TransferTest
php artisan make:test MerchantTest
php artisan make:test AgentTest
php artisan make:test DealTest
php artisan make:test CardTest
php artisan make:test KycTest
php artisan make:test AdminTest
php artisan make:test NotificationTest
php artisan make:test TwoFactorTest
```
