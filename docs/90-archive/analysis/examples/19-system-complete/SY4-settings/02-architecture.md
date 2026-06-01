# 02 - بنية النظام: SettingsController → SettingsService → system_settings → Redis (Architecture)

## نظرة عامة على البنية (Architecture Overview)

تتبع بنية SY4-settings نمط الطبقات (Layered Architecture) مع فصل كامل بين المسؤوليات:

```
┌─────────────────────────────────────────────────────────────────┐
│                         Client (React Admin)                     │
│                    JWT Token (auth:api)                          │
└─────────────────────────┬───────────────────────────────────────┘
                          │ HTTP Requests
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Routes: api.php                              │
│  GET|PUT /admin/system/settings/{group?}                       │
│  Middleware: auth:api, admin                                    │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                SystemSettingsController                          │
│  - index() -> SettingsService::getAll()                        │
│  - update() -> SettingsService::setGroup()                     │
│  - testMail() -> SettingsService::testSmtpConnection()        │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SettingsService (Core)                        │
│  - getAll(): array             - getByGroup($group): array      │
│  - get($key, $default): mixed  - setGroup($group, $data): void │
│  - set($key, $value): void     - testSmtpConnection(): bool    │
└───────┬─────────────────┬──────────────────┬───────────────────┘
        │                 │                  │
        ▼                 ▼                  ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────────┐
│  Settings     │ │  Settings    │ │  Settings        │
│  Validator    │ │  CacheMgr    │ │  Helper          │
│  - validate() │ │  - get()    │ │  - system_       │
│  - rules()    │ │  - set()    │ │    settings()    │
└──────────────┘ │  - forget() │ └──────────────────┘
                 └──────┬───────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────────┐
│                        Redis Cache                              │
│  Key: system_settings:{group}:{key}                             │
│  TTL: 3600 seconds (1 hour)                                     │
│  استراتيجية: Cache-Aside                                       │
│  عند التحديث: مسح المفاتيح المتعلقة بالمجموعة                   │
└─────────────────────────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────────┐
│                    MySQL Database                                │
│  Table: system_settings                                         │
│  Columns: id, group, key, value, type, description              │
│  Created: migration_create_system_settings_table               │
└─────────────────────────────────────────────────────────────────┘
```

## تدفق الطلب (Request Flow)

### قراءة جميع الإعدادات (GET)

```
1. Controller::index() تستدعي SettingsService::getAll()
2. SettingsService تتحقق من Redis cache أولاً
3. إذا وجدت: تعيد البيانات من Redis
4. إذا لم تجد: تجلب من MySQL وتخزن في Redis
5. تعيد البيانات مرتبة حسب المجموعة
```

### تحديث إعدادات (PUT)

```
1. Controller::update() تستقبل البيانات المطلوب تحديثها
2. SettingsValidator تتحقق من صحة البيانات حسب المجموعة
3. SettingsService::setGroup() تنفذ التحديث
4. لكل إعداد: تحديث في MySQL (upsert)
5. إرسال حدث SettingUpdated
6. مستمع الحدث يمسح مفاتيح Redis الخاصة بالمجموعة
7. إعادة الاستجابة للمستخدم
```

## مكونات البنية (Architecture Components)

### 1. طبقة التوجيه (Routing Layer)

```php
// // ملف: routes/api.php
// // جميع المسارات محمية بـ auth:api (JWT) و admin
Route::middleware(['auth:api', 'admin'])->prefix('admin/system')->group(function () {
    Route::get('/settings', [SystemSettingsController::class, 'index']);
    Route::put('/settings/{group}', [SystemSettingsController::class, 'update']);
    Route::post('/settings/mail/test', [SystemSettingsController::class, 'testMail']);
});
```

### 2. طبقة التحكم (Controller Layer)

```php
// // SystemSettingsController يستقبل الطلبات ويمررها للخدمة
// // لا يحتوي على منطق أعمال - فقط تنسيق
class SystemSettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService,
        private SettingsValidator $validator
    ) {}
}
```

### 3. طبقة الخدمة (Service Layer)

```php
// // SettingsService يحتوي على كل منطق الأعمال
// // يتعامل مع قاعدة البيانات و Redis
class SettingsService
{
    // // يحصل على إعداد معين مع قيمة افتراضية
    public function get(string $key, mixed $default = null): mixed {}

    // // يحصل على جميع الإعدادات مجمعة حسب الفئة
    public function getAll(): array {}

    // // يحدث مجموعة كاملة من الإعدادات
    public function setGroup(string $group, array $data): void {}
}
```

### 4. طبقة التخزين المؤقت (Cache Layer)

```php
// // Cache-Aside pattern
// // 1. حاول قراءة من Redis
// // 2. إذا لم تجد -> اقرأ من MySQL -> خزن في Redis
// // 3. عند التحديث -> امسح من Redis
class SettingsCacheManager
{
    private const TTL = 3600; // ساعة واحدة
    private const PREFIX = 'system_settings';
}
```
