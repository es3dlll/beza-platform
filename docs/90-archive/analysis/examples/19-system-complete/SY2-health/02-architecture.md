# 02 - العمارة: المكونات والاعتماديات (Architecture: Components & Dependencies)

**الرمز التشغيلي:** SY2-health  
**النوع:** توثيق معماري (Architecture documentation)

---

## رسم العمارة (Architecture Diagram)

```
┌─────────────────────────────────────────────────────────────┐
│                        HTTP Client                          │
│              (Flutter / React / CURL / Nagios)               │
└──────────────────┬──────────────────────────────────────────┘
                   │  GET /system/health
                   │  GET /system/health/db
                   │  GET /admin/system/health
                   ▼
┌──────────────────────────────────────────────────────────────┐
│                    Routes (api.php)                          │
│  auth:api middleware للـ admin endpoints                      │
│  throttle:60,1 لجميع النقاط (rate limiting)                   │
└──────────────────┬───────────────────────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────────────────────┐
│                   HealthController                           │
│  - index() → /system/health                                  │
│  - checkDb() → /system/health/db                             │
│  - checkRedis() → /system/health/redis                       │
│  - checkCache() → /system/health/cache                       │
│  - checkQueue() → /system/health/queue                       │
│  - checkRequirements() → /system/health/requirements          │
│  - checkStorage() → /system/health/storage                   │
│  - adminDashboard() → /admin/system/health                   │
└──────────────────┬───────────────────────────────────────────┘
                   │
                   ▼
┌──────────────────────────────────────────────────────────────┐
│                      HealthService                           │
│  نقطة الدخول لجميع الفحوصات، تدير التخزين المؤقت             │
│  والاستثناءات وتجميع النتائج                                  │
└───┬──────────┬──────────┬──────────┬──────────┬─────────────┘
    │          │          │          │          │
    ▼          ▼          ▼          ▼          ▼
┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────────┐
│Database│ │ Redis  │ │ Cache  │ │ Queue  │ │Requirements  │
│Checker │ │Checker │ │Checker │ │Checker │ │  Checker     │
└───┬────┘ └───┬────┘ └───┬────┘ └───┬────┘ └──────┬───────┘
    │          │          │          │              │
    ▼          ▼          ▼          ▼              ▼
┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────────┐
│  DB    │ │ Redis  │ │ Cache  │ │ Queue  │ │  PHP         │
│  conn  │ │  conn  │ │ driver │ │ driver │ │  Extensions  │
└────────┘ └────────┘ └────────┘ └────────┘ └──────────────┘

                    ┌─────────────────────┐
                    │  StorageChecker     │
                    │  - logs dir         │
                    │  - cache dir        │
                    │  - uploads dir      │
                    └─────────────────────┘
```

---

## المكونات الأساسية (Core Components)

### 1. طبقة التوجيه (Route Layer) — `routes/api.php`

```php
// المسارات العامة (اختياري المصادقة)
Route::get('/system/health', [HealthController::class, 'index']);
Route::get('/system/health/db', [HealthController::class, 'checkDb']);
Route::get('/system/health/redis', [HealthController::class, 'checkRedis']);
Route::get('/system/health/cache', [HealthController::class, 'checkCache']);
Route::get('/system/health/queue', [HealthController::class, 'checkQueue']);
Route::get('/system/health/requirements', [HealthController::class, 'checkRequirements']);
Route::get('/system/health/storage', [HealthController::class, 'checkStorage']);

// المسارات المحمية (مطلوب دور admin)
Route::group(['middleware' => ['auth:api', 'role:admin']], function () {
    Route::get('/admin/system/health', [HealthController::class, 'adminDashboard']);
});
```

### 2. طبقة التحكم (Controller Layer)

`HealthController` يستقبل الطلبات ويوجهها إلى `HealthService`. كل دالة في الكنترولر تتعامل مع نقطة نهاية واحدة.

### 3. طبقة الخدمات (Service Layer)

`HealthService` هو المسؤول عن:
- استدعاء المدققين المناسبين
- تخزين النتائج في الكاش لمدة 30 ثانية
- تجميع النتائج في مصفوفة موحدة
- إطلاق حدث `HealthCheckCompleted`

### 4. طبقة المدققين (Checker Layer)

كل مدقق هو كلاس مستقل مسؤول عن فحص خدمة محددة:

| المدقق (Checker) | يفحص (Checks) | المصدر (Source) |
|-----------------|--------------|-----------------|
| `DatabaseChecker` | اتصال MySQL + وقت الاستعلام | `DB::select('SELECT 1')` |
| `RedisChecker` | اتصال Redis + ping | `Redis::connection()->ping()` |
| `CacheChecker` | كتابة وقراءة مؤقتة | `Cache::put()` / `Cache::get()` |
| `QueueChecker` | اتصال قائمة الانتظار | `Queue::getDefaultDriver()` |
| `RequirementsChecker` | إضافات PHP وإصداراتها | `extension_loaded()` / `phpversion()` |
| `StorageChecker` | صلاحيات الكتابة للمجلدات | `is_writable()` |

---

## تدفق الاعتماديات (Dependency Flow)

```
HealthController
    └── HealthService
            ├── DatabaseChecker
            │       └── Illuminate\Support\Facades\DB
            ├── RedisChecker
            │       └── Illuminate\Support\Facades\Redis
            ├── CacheChecker
            │       └── Illuminate\Support\Facades\Cache
            ├── QueueChecker
            │       └── Illuminate\Support\Facades\Queue
            ├── RequirementsChecker
            │       └── PHP built-in functions
            └── StorageChecker
                    └── PHP filesystem functions
```

---

## قرارات معمارية (Architectural Decisions)

| القرار (Decision) | البديل (Alternative) | لماذا اخترنا هذا (Why) |
|------------------|---------------------|----------------------|
| كلاس منفصل لكل مدقق | كلاس واحد ضخم | قابلية التوسع والاختبار |
| تخزين مؤقت 30 ثانية | بدون تخزين مؤقت | منع هجمات DoS |
| استثناءات متوقعة لكل خدمة | استثناء عام | تحديد المشكلة بدقة |
| مصادقة اختيارية للعامة | مطلوبة دائماً | أدوات المراقبة تحتاج وصول بدون توكن |
| استخدام JWT (auth:api) | Sanctum | التوافق مع نظام المصادقة الموجود |
