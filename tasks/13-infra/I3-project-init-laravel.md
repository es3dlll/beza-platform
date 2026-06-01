# I3: إنشاء مشروع Laravel وتهيئته

**المعرف:** `I3-project-init-laravel`  
**الوحدة:** ⚙️ بنية تحتية  
**الأولوية:** 🔴 P0 — حرجة  

---

## الهدف

إنشاء مشروع Laravel 12 جديد في المسار الصحيح، تثبيت الحزم الأساسية، وإعداد الهيكل المعياري.

## الخطة

### 1. إنشاء المشروع

```bash
cd C:\laragon\www
composer create-project laravel/laravel beza-platform --prefer-dist --no-interaction
```

> ملاحظة: المشروع الحالي هو `Beza-Platform` (بحرف B كبير و dash).  
> قرار: **نستخدم المسار الحالي** وننشئ Laravel داخله بعد ترتيب المجلدات.

### 2. الحزم الأساسية

```bash
# المصادقة
composer require laravel/sanctum

# الصفوف والخلفية
composer require laravel/horizon

# Redis
composer require predis/predis

# الصلاحيات (Admin + Merchant + Agent roles)
composer require spatie/laravel-permission

# IDE Helper (تطوير)
composer require --dev barryvdh/laravel-ide-helper

# Debug Bar (تطوير)
composer require --dev barryvdh/laravel-debugbar

# Laravel Pulse (مراقبة)
composer require laravel/pulse
```

### 3. هيكل الوحدات (Modules)

بدلاً من `app/Models` المسطح — نستخدم **Modular Monolith**:

```
app/Modules/
├── Auth/         🔐
│   ├── Controllers/
│   ├── Services/
│   ├── Models/
│   ├── Policies/
│   └── routes/
├── Wallet/       💰
│   ├── Controllers/
│   ├── Services/
│   ├── Models/
│   ├── Policies/
│   └── routes/
├── Transaction/  💸
├── Merchant/     🏪
├── Agent/        🤝
├── Admin/        ⚙️
├── KYC/          🆔
├── Notification/ 🔔
├── FX/           💱
└── Compliance/   📋
```

### 4. Core المشترك

```
app/Core/
├── ValueObjects/
│   ├── Money.php         # bigint (فلس) + Currency
│   ├── Currency.php      # SYP, USD, EUR
│   └── Amount.php        # تحويل بين string/bigint
├── Enums/
│   ├── TransactionType.php
│   ├── TransactionStatus.php
│   ├── WalletStatus.php
│   └── KycLevel.php
├── Interfaces/
│   ├── ModuleServiceInterface.php
│   └── EventBusInterface.php
├── Traits/
│   ├── HasUuid.php
│   └── HasMoneyCasts.php
└── Exceptions/
    ├── DomainException.php
    ├── InsufficientFundsException.php
    └── InvalidAmountException.php
```

### 5. الإعدادات

```php
// config/modules.php - إعدادات الوحدات
return [
    'auth' => ['enabled' => true, 'otp_required' => false],
    'wallet' => ['currencies' => ['SYP', 'USD'], 'default' => 'SYP'],
    'transaction' => ['max_amount' => 100000000], // 10M SYP بالفلس
];
```

### 6. الترحيلات (Migrations)

نظام الترقيم: `YYYY_MM_DD_HHMMSS_{module}_{table}.php`

```
database/migrations/
├── 2026_06_01_000001_core_create_users_table.php
├── 2026_06_01_000002_core_create_wallets_table.php
├── 2026_06_01_000003_core_create_currencies_table.php
├── 2026_06_01_000004_core_create_transactions_table.php
├── 2026_06_01_000005_auth_create_personal_access_tokens.php
└── 2026_06_02_000001_auth_create_otp_codes_table.php
```

### 7. الاختبارات

```
tests/
├── Feature/
│   ├── Modules/
│   │   ├── Auth/
│   │   ├── Wallet/
│   │   └── Transaction/
│   └── Security/
└── Unit/
    ├── Core/
    │   └── MoneyTest.php
    └── Modules/
```

## معايير القبول

- [ ] `composer install` يعمل بدون أخطاء
- [ ] `php artisan serve` يعرض صفحة Laravel
- [ ] Sanctum منصّب ومهيّأ
- [ ] Horizon منصّب ومهيّأ
- [ ] Spatie Permission منصّب
- [ ] هيكل `app/Modules/` و `app/Core/` جاهز
- [ ] `php artisan test` يمر بنجاح
