# 03 - تدفق البيانات: القراءة والكتابة مع التخزين المؤقت (Data Flow Sequence)

## تدفق القراءة مع التخزين المؤقت (Read Flow with Cache)

### السيناريو 1: وجود البيانات في الكاش (Cache Hit)

```
Client (React)           Controller          SettingsService         Redis           MySQL
     │                       │                     │                  │               │
     │  GET /admin/system/   │                     │                  │               │
     │  settings             │                     │                  │               │
     │──────────────────────>│                     │                  │               │
     │                       │ getAll()            │                  │               │
     │                       │────────────────────>│                  │               │
     │                       │                     │ getAllFromCache() │               │
     │                       │                     │─────────────────>│               │
     │                       │                     │                  │               │
     │                       │                     │  <data JSON>     │               │
     │                       │                     │<─────────────────│               │
     │                       │                     │                  │               │
     │                       │  <all settings>     │                  │               │
     │                       │<────────────────────│                  │               │
     │  <200 OK + settings>  │                     │                  │               │
     │<──────────────────────│                     │                  │               │
```

**الخطوات:**
1. يرسل العميل طلب GET مع توكن JWT في الهيدر `Authorization: Bearer <token>`
2. يمر الطلب عبر Middleware `auth:api` للتحقق من التوكن
3. يمر عبر Middleware `admin` للتحقق من صلاحية المسؤول
4. `SettingsService::getAll()` يستدعي `SettingsCacheManager::getAll()`
5. إذا وجدت البيانات في Redis - تعاد فوراً بدون لمس MySQL

### السيناريو 2: عدم وجود البيانات في الكاش (Cache Miss)

```
Client (React)           Controller          SettingsService         Redis           MySQL
     │                       │                     │                  │               │
     │  GET /admin/system/   │                     │                  │               │
     │  settings             │                     │                  │               │
     │──────────────────────>│                     │                  │               │
     │                       │ getAll()            │                  │               │
     │                       │────────────────────>│                  │               │
     │                       │                     │ getAllFromCache() │               │
     │                       │                     │─────────────────>│               │
     │                       │                     │                  │               │
     │                       │                     │     null         │               │
     │                       │                     │<─────────────────│               │
     │                       │                     │                  │               │
     │                       │                     │ getAllFromDB()   │               │
     │                       │                     │─────────────────────────────────>
     │                       │                     │                  │               │
     │                       │                     │  <all rows>      │               │
     │                       │                     │<─────────────────────────────────│
     │                       │                     │                  │               │
     │                       │                     │ setAllToCache()  │               │
     │                       │                     │─────────────────>│               │
     │                       │                     │                  │               │
     │                       │  <all settings>     │                  │               │
     │                       │<────────────────────│                  │               │
     │  <200 OK + settings>  │                     │                  │               │
     │<──────────────────────│                     │                  │               │
```

**الخطوات:**
1. نفس الخطوات الأولى لكن Redis لا يحتوي على البيانات
2. يتم جلب البيانات من MySQL
3. تُخزَّن في Redis لمدة ساعة (TTL=3600)
4. تُعاد للعميل

## تدفق التحديث (Update/PUT Flow)

```
Client (React)           Controller          SettingsService         Redis          MySQL         Event
     │                       │                     │                  │               │            │
     │  PUT /admin/system/   │                     │                  │               │            │
     │  settings/general     │                     │                  │               │            │
     │  {app_name: "Beza"}  │                     │                  │               │            │
     │──────────────────────>│                     │                  │               │            │
     │                       │ validate($data)     │                  │               │            │
     │                       │────────────────────>│                  │               │            │
     │                       │                     │                  │               │            │
     │                       │  validated data     │                  │               │            │
     │                       │<────────────────────│                  │               │            │
     │                       │                     │                  │               │            │
     │                       │ setGroup('general', │                  │               │            │
     │                       │   $validated)       │                  │               │            │
     │                       │────────────────────>│                  │               │            │
     │                       │                     │ DB::transaction  │               │            │
     │                       │                     │──────────────────────────────>               │
     │                       │                     │                  │               │            │
     │                       │                     │ updateOrInsert   │               │            │
     │                       │                     │─────────────────────────────────>            │
     │                       │                     │                  │               │            │
     │                       │                     │                  │ forget('general')│         │
     │                       │                     │─────────────────>│               │            │
     │                       │                     │                  │               │            │
     │                       │                     │ event(SettingUpdated)            │            │
     │                       │                     │─────────────────────────────────────────────>
     │                       │                     │                  │               │            │
     │                       │  <success response> │                  │               │            │
     │                       │<────────────────────│                  │               │            │
     │  <200 OK + message>   │                     │                  │               │            │
     │<──────────────────────│                     │                  │               │            │
```

**الخطوات بالتفصيل:**
1. يرسل العميل طلب PUT مع JSON body
2. `SettingsValidator` تتحقق من صحة الحقول حسب المجموعة
3. `SettingsService::setGroup()` تبدأ transaction
4. لكل حقل في البيانات المطلوب تحديثها:
   - استخدام `updateOrInsert` لتحديث أو إدراج سجل جديد
   - التحقق من نوع القيمة (string, integer, boolean, json)
5. بعد نجاح كل التحديثات، يتم مسح مفاتيح Redis للمجموعة
6. إرسال حدث `SettingUpdated`
7. Commit للـ transaction

## تدفق قراءة إعداد واحد (Single Setting Read)

```
AnyComponent           SettingsHelper           SettingsCacheMgr          Redis           MySQL
     │                       │                       │                    │               │
     │ system_settings(      │                       │                    │               │
     │  'general.app_name',  │                       │                    │               │
     │  'Beza')             │                       │                    │               │
     │──────────────────────>│                       │                    │               │
     │                       │ get('general.app_name')                    │               │
     │                       │──────────────────────>│                    │               │
     │                       │                       │ getFromCache()     │               │
     │                       │                       │───────────────────>│               │
     │                       │                       │                    │               │
     │                       │                       │  <value or null>   │               │
     │                       │                       │<───────────────────│               │
     │                       │                       │                    │               │
     │                       │    (if null from cache) │                  │               │
     │                       │                       │ getFromDB()        │               │
     │                       │                       │──────────────────────────────────>
     │                       │                       │                    │               │
     │                       │                       │  <row value>       │               │
     │                       │                       │<──────────────────────────────────│
     │                       │                       │                    │               │
     │                       │                       │ setToCache()       │               │
     │                       │                       │───────────────────>│               │
     │                       │                       │                    │               │
     │                       │  "Beza" (or default)  │                    │               │
     │                       │<──────────────────────│                    │               │
     │  "Beza"              │                       │                    │               │
     │<──────────────────────│                       │                    │               │
```

## استراتيجية التخزين المؤقت (Cache Strategy)

```php
// // Cache-Aside Pattern مع TTL متجدد
// // يتم تحديث TTL في كل قراءة ناجحة من Redis

public function remember(string $key, mixed $default): mixed
{
    $cacheKey = "system_settings:{$key}";

    // 1. محاولة القراءة من Redis
    $cached = Redis::get($cacheKey);
    if ($cached !== null) {
        // تجديد TTL - // إعادة تعيين صلاحية الكاش
        Redis::expire($cacheKey, self::TTL);
        return unserialize($cached);
    }

    // 2. إذا لم نجد في الكاش -> اقرأ من MySQL
    $setting = SystemSetting::where('key', $key)->first();
    $value = $setting ? $setting->getTypedValue() : $default;

    // 3. خزن في Redis
    Redis::setex($cacheKey, self::TTL, serialize($value));

    return $value;
}
```
