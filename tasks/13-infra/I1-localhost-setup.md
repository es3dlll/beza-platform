# I1: إعداد بيئة التطوير المحلية

**المعرف:** `I1-localhost-setup`  
**الوحدة:** ⚙️ بنية تحتية  
**الأولوية:** 🔴 P0 — حرجة  

---

## الهدف

تهيئة بيئة التطوير المحلية بالكامل لتشغيل منصة Beza.

## المتطلبات

| الأداة | الإصدار | ملاحظات |
|--------|---------|---------|
| PHP | 8.4+ | 8.5.6 متوفر |
| Composer | 2.8+ | 2.9.4 متوفر |
| PostgreSQL | 17+ | مطلوب |
| Redis | 7+ | مطلوب للكاش والصفوف |
| Node.js | 22+ | متوفر |
| Laragon | أحدث | اختياري — البيئة الحالية |

## هيكل المشروع

```
beza-platform/
├── app/
│   ├── Modules/          ← وحدات التطبيق (Modular Monolith)
│   │   ├── Auth/         🔐 المصادقة
│   │   ├── Wallet/       💰 المحفظة
│   │   ├── Transaction/  💸 المعاملات
│   │   ├── Merchant/     🏪 التجار
│   │   ├── Agent/        🤝 الوكلاء
│   │   ├── Admin/        ⚙️ الإدارة
│   │   ├── KYC/          🆔 التحقق من الهوية
│   │   ├── Notification/ 🔔 الإشعارات
│   │   ├── Card/         💳 البطاقات
│   │   ├── FX/           💱 العملات
│   │   ├── Compliance/   📋 الامتثال
│   │   └── Shared/       🔄 مشترك
│   ├── Core/
│   │   ├── ValueObjects/  # Money, Currency, Amount
│   │   ├── Enums/         # Status, Type, Channel
│   │   ├── Interfaces/    # ModuleService, EventBus
│   │   ├── Traits/        # HasUuid, HasMoney
│   │   └── Exceptions/    # DomainException, InsufficientFunds
│   └── Services/          # WebhookSignature, RateLimiter
├── config/
│   ├── modules/           # إعدادات كل وحدة
│   ├── rate-limiting.php  # حدود الطلبات
│   └── services.php       # Webhooks, API keys
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php            # نقاط API العامة
│   ├── merchant.php       # نقاط API للتجار
│   ├── admin.php          # نقاط API للمشرفين
│   └── webhooks.php       # نقاط الـ Webhooks
├── tests/
│   ├── Feature/           # اختبارات تكاملية
│   │   └── Modules/       # لكل وحدة
│   └── Unit/              # اختبارات وحدة
├── docs/                  # التوثيق
├── tasks/                 # المهام
└── agents/                # وكلاء AI (submodules)
```

## خطوات التثبيت

```bash
# 1. إنشاء مشروع Laravel
composer create-project laravel/laravel beza-platform --prefer-dist

# 2. تثبيت الحزم الأساسية
composer require laravel/sanctum
composer require laravel/horizon
composer require predis/predis
composer require spatie/laravel-permission

# 3. إعداد قاعدة البيانات
# إنشاء قاعدة PostgreSQL: beza_platform
# تحديث .env

# 4. تشغيل الترحيلات
php artisan migrate
php artisan db:seed

# 5. تشغيل الخادم
php artisan serve
```

## ملف .env

```
APP_NAME=BezaPlatform
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=beza_platform
DB_USERNAME=postgres
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SANCTUM_STATEFUL_DOMAINS=localhost:8000
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## معايير القبول

- [ ] `php artisan serve` يعمل ويعيد صفحة Laravel
- [ ] `php artisan migrate` يعمل بدون أخطاء
- [ ] `php artisan test` يمر بنجاح
- [ ] الاتصال بقاعدة PostgreSQL ناجح
- [ ] Redis يعمل مع Laravel Horizon
