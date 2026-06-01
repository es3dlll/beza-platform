# 05 - الميغريشن (Migrations)

## لا يوجد ميجريشن خاص بالمثبت

المثبت **لا يملك ميجريشن خاص به** لأنه لا ينشئ جداول خاصة. بدلاً من ذلك، يقوم بتشغيل **ميجريشن التطبيق الأصلي** عبر:

```php
// يتم استدعاء Artisan داخل InstallerController
use Illuminate\Support\Facades\Artisan;

$exitCode = Artisan::call('migrate', [
    '--force' => true,
    '--seed'  => false,  // يتم التشغيل بشكل منفصل
]);
```

## ترتيب الميجريشن التي يتم تشغيلها

عند استدعاء `php artisan migrate --force`، يتم تشغيل جميع الميجريشنات المخزنة في `database/migrations/`:

```
database/migrations/
├── 0001_01_01_000000_create_users_table.php
├── 0001_01_01_000001_create_cache_table.php
├── 0001_01_01_000002_create_jobs_table.php
├── 2024_01_01_000001_create_wallets_table.php
├── 2024_01_01_000002_create_transactions_table.php
├── 2024_01_01_000003_create_kyc_documents_table.php
├── 2024_01_01_000004_create_notifications_table.php
├── 2024_01_02_000001_add_two_factor_to_users.php
├── 2024_01_02_000002_create_currencies_table.php
├── 2024_02_01_000001_create_exchange_rates_table.php
├── 2024_02_01_000002_create_transfer_limits_table.php
└── 2024_03_01_000001_create_audit_logs_table.php
```

## إعدادات JWT

بعد كتابة `.env` وقبل الميجريشن، يقوم المثبت بتوليد مفاتيح JWT:

```php
// يتم توليد JWT_SECRET تلقائياً
Artisan::call('jwt:secret', [
    '--force' => true,
]);
```

هذا الأمر ينفذ الأوامر التالية:
1. توليد مفتاح عشوائي آمن (base64)
2. كتابته في `.env` كـ `JWT_SECRET=...`
3. التأكد من عدم وجود قيمة سابقة

## إعدادات APP_KEY

```php
// يتم توليد APP_KEY تلقائياً
Artisan::call('key:generate', [
    '--force' => true,
]);
```

## Seeders

بعد الميجريشن، يتم تشغيل:

```php
Artisan::call('db:seed', [
    '--force' => true,
]);
```

هذا يشغل:
- `DatabaseSeeder` — الذي يستدعي جميع `Seeders` الفرعية
- `CurrencySeeder` — إضافة العملات (SYP, USD, EUR, TRY)
- `SettingSeeder` — إعدادات المنصة الافتراضية

## ملاحظة مهمة

لأن المثبت يعمل **مرة واحدة فقط**، فإنه لا يحتاج إلى `down()` method أو `rollback`. جميع الميجريشنات تستخدم `--force` لأن البيئة ليست `local` بالضرورة (قد تكون production).
