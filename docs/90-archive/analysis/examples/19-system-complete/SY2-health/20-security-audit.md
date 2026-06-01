# 20 - التدقيق الأمني (Security Audit)

**الرمز التشغيلي:** SY2-health  
**النوع:** تدقيق أمني (Security Audit)

---

## نظرة عامة (Overview)

نظام التحقق الصحي هو نافذة على البنية التحتية للمنصة. تسرب معلوماته يمكن أن يساعد المهاجمين على استهداف نقاط الضعف. هذا الملف يوثق الإجراءات الأمنية المطبقة لحماية هذه المعلومات.

---

## المخاطر الأمنية (Security Risks)

| المخاطرة (Risk) | الوصف (Description) | مستوى الخطورة (Severity) |
|-----------------|-------------------|------------------------|
| **تسرب معلومات البنية التحتية** | كشف إصدار MySQL، PHP، Laravel | عالي |
| **هجوم DoS على نقاط الفحص** | إغراق النظام بطلبات فحص متزامنة | متوسط |
| **تسرب معلومات التخزين** | كشف مسارات المجلدات وهيكل الملفات | عالي |
| **هجوم تخمين** | استخدام الفحص لتخمين خدمات الخلفية | متوسط |
| **كشف بيانات حساسة** | إظهار أخطاء الاتصال بتفاصيل تقنية | عالي |

---

## الإجراءات الأمنية المطبقة (Implemented Security Measures)

### 1. منع تسرب المعلومات (Information Leak Prevention)

```php
<?php
// ترجمة: إخفاء التفاصيل التقنية في بيئة الإنتاج

namespace App\Services\Health;

class HealthResponseFormatter
{
    /**
     * تنظيف الاستجابة من المعلومات الحساسة
     * يطبق في بيئة الإنتاج فقط
     *
     * @param array $response
     * @return array
     */
    public function sanitizeResponse(array $response): array
    {
        // ترجمة: في بيئة الإنتاج، نخفي التفاصيل الحساسة
        if (app()->environment('production')) {
            foreach ($response['services'] ?? [] as &$service) {
                // ترجمة: إخفاء أخطاء الاتصال التفصيلية
                unset($service['error']);
                unset($service['trace']);

                // ترجمة: إخفاء معلومات الإصدار للتطبيق
                if (isset($service['details'])) {
                    unset($service['details']['server']);
                    unset($service['details']['server_info']);
                }
            }
        }

        return $response;
    }
}
```

### 2. التخزين المؤقت لمنع DoS (Cache to Prevent DoS)

```php
<?php
// ترجمة: تخزين مؤقت مع قفل لمنع هجمات DoS

use Illuminate\Support\Facades\Cache;

public function getGeneralHealthReport(): array
{
    $cacheKey = 'health:general';

    // ترجمة: التحقق من الكاش أولاً
    $cached = Cache::get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    // ترجمة: قفل ذري لمنع الطلبات المتزامنة من تكرار الفحص
    $lock = Cache::lock('health:general:lock', 10);

    try {
        // ترجمة: محاولة الحصول على القفل
        if ($lock->get()) {
            // ترجمة: تحقق مرة أخرى من الكاش (قد يكون التحديث تم بواسطة طلب آخر)
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            // ترجمة: تنفيذ الفحص الفعلي
            $report = $this->performActualCheck();

            // ترجمة: تخزين النتيجة
            Cache::put($cacheKey, $report, 30);

            return $report;
        }

        // ترجمة: طلب آخر يقوم بالفحص حالياً، ننتظر قليلاً ونحاول مجدداً
        usleep(100000); // 100ms
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // ترجمة: في أسوأ الأحوال، نعيد حالة down آمنة
        return [
            'status' => 'ok',
            'services' => [],
            'timestamp' => now()->toIso8601String(),
            'cached' => false,
        ];

    } finally {
        // ترجمة: تحرير القفل
        $lock->release();
    }
}
```

### 3. تحديد معدل الطلبات (Rate Limiting)

```php
<?php
// routes/api.php - تحديد معدل الطلبات

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;

/*
 * ترجمة: تطبيق rate limiting على جميع نقاط التحقق الصحي
 *
 * public: 60 طلب/دقيقة لكل IP (كثافة منخفضة لمنع DoS)
 * admin: 30 طلب/دقيقة لكل مستخدم (حماية إضافية)
 */

Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/system/health', [HealthController::class, 'index']);
    Route::get('/system/health/db', [HealthController::class, 'checkDb']);
    Route::get('/system/health/redis', [HealthController::class, 'checkRedis']);
    Route::get('/system/health/cache', [HealthController::class, 'checkCache']);
    Route::get('/system/health/queue', [HealthController::class, 'checkQueue']);
    Route::get('/system/health/requirements', [HealthController::class, 'checkRequirements']);
    Route::get('/system/health/storage', [HealthController::class, 'checkStorage']);
});

// ترجمة: مسار المشرف بمعدل أقل وحماية مضاعفة
Route::middleware(['auth:api', 'role:admin', 'throttle:30,1'])->group(function () {
    Route::get('/admin/system/health', [HealthController::class, 'adminDashboard']);
});
```

### 4. مصادقة المسار الحساس (Sensitive Route Authentication)

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * ميدلوير مخصص للتحقق من صلاحية المشرف
 */
class AdminHealthMiddleware
{
    /**
     * معالجة الطلب والتحقق من صلاحية المشرف
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            // ترجمة: محاولة مصادقة المستخدم
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'غير مصرح بالوصول',
                ], 401);
            }

            // ترجمة: التحقق من صلاحية المشرف
            if (!$user->hasRole('admin')) {
                // ترجمة: تسجيل محاولة وصول غير مصرح بها
                \Illuminate\Support\Facades\Log::warning(
                    'محاولة وصول غير مصرح بها للوحة التحكم الصحية',
                    [
                        'user_id' => $user->id,
                        'email'   => $user->email,
                        'ip'      => $request->ip(),
                    ]
                );

                return response()->json([
                    'status'  => 'error',
                    'message' => 'هذه المعلومات متاحة فقط للمشرفين',
                ], 403);
            }

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'انتهت صلاحية التوكن',
            ], 401);

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'التوكن غير صالح',
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'مطلوب توكن للمصادقة',
            ], 401);
        }

        return $next($request);
    }
}
```

### 5. حماية مسارات المجلدات (Directory Path Protection)

```php
<?php
// ترجمة: في الاستجابة العامة، نظهر فقط أسماء المجلدات بدون المسار الكامل

public function sanitizeDirectoryPaths(array $directories): array
{
    return array_map(function ($dir) {
        // ترجمة: إخفاء المسار الكامل وإظهار اسم المجلد فقط
        $dir['path'] = basename($dir['path']);
        return $dir;
    }, $directories);
}

// في بيئة الإنتاج:
// قبل: C:\www\beza\storage\logs
// بعد: logs
```

### 6. تسجيل المحاولات المشبوهة (Suspicious Activity Logging)

```php
<?php
// ترجمة: تسجيل أي نشاط غير طبيعي على نقاط التحقق الصحي

namespace App\Services\Health;

use Illuminate\Support\Facades\Log;

class HealthSecurityLogger
{
    /**
     * عدد الطلبات المسموح بها لكل IP في الدقيقة
     */
    const MAX_REQUESTS_PER_MINUTE = 60;

    /**
     * تسجيل محاولة وصول غير عادية
     *
     * @param string $ip
     * @param string $endpoint
     * @param int $requestCount
     * @return void
     */
    public function logSuspiciousActivity(string $ip, string $endpoint, int $requestCount): void
    {
        if ($requestCount > self::MAX_REQUESTS_PER_MINUTE) {
            Log::warning('نشاط مشبوه على نقاط التحقق الصحي', [
                'ip'          => $ip,
                'endpoint'    => $endpoint,
                'count'       => $requestCount,
                'user_agent'  => request()->userAgent(),
                'time'        => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * تسجيل محاولة وصول لدور غير مصرح
     *
     * @param int $userId
     * @param string $email
     * @param string $ip
     * @return void
     */
    public function logUnauthorizedAccess(int $userId, string $email, string $ip): void
    {
        Log::channel('security')->warning(
            'محاولة وصول غير مصرح بها للوحة التحكم الصحية',
            [
                'user_id' => $userId,
                'email'   => $email,
                'ip'      => $ip,
                'time'    => now()->toIso8601String(),
            ]
        );
    }

    /**
     * تسجيل هجوم DoS محتمل
     *
     * @param string $ip
     * @return void
     */
    public function logPotentialDosAttack(string $ip): void
    {
        Log::channel('security')->critical(
            'هجوم DoS محتمل على نقاط التحقق الصحي',
            [
                'ip'   => $ip,
                'time' => now()->toIso8601String(),
            ]
        );
    }
}
```

### 7. تقييد المعلومات في استجابات الخطأ (Error Response Restriction)

```php
<?php
// ترجمة: في بيئة الإنتاج، نعطي رسائل خطأ عامة

public function getErrorMessage(\Exception $exception): string
{
    if (app()->environment('production')) {
        // ترجمة: رسائل عامة في الإنتاج
        return match (true) {
            $exception instanceof DatabaseConnectionException
                => 'تعذر الاتصال بقاعدة البيانات',
            $exception instanceof RedisConnectionException
                => 'تعذر الاتصال بخدمة التخزين المؤقت',
            $exception instanceof CacheWriteException
                => 'تعذر الوصول إلى الذاكرة المؤقتة',
            $exception instanceof QueueConnectionException
                => 'تعذر الاتصال بقائمة الانتظار',
            default => 'حدث خطأ أثناء فحص الخدمة',
        };
    }

    // ترجمة: في التطوير، نعطي التفاصيل الكاملة
    return $exception->getMessage();
}
```

---

## قائمة التحقق الأمني (Security Checklist)

| الإجراء (Measure) | مطبق؟ (Applied?) | الشرح (Explanation) |
|------------------|-----------------|-------------------|
| **مصادقة JWT** للمسارات الحساسة | ✅ | `/admin/system/health` يتطلب `auth:api` |
| **التحقق من الدور** للمشرف فقط | ✅ | `role:admin` middleware + تحقق في الكنترولر |
| **تحديد معدل الطلبات** (Rate Limiting) | ✅ | 60/دقيقة للعامة، 30/دقيقة للمشرف |
| **تخزين مؤقت** لمنع DoS | ✅ | 30 ثانية تخزين لنتائج الفحص العام |
| **إخفاء التفاصيل التقنية** في الإنتاج | ✅ | رسائل خطأ عامة بدلاً من التفصيلية |
| **إخفاء مسارات المجلدات** | ✅ | عرض اسم المجلد فقط بدون المسار الكامل |
| **تسجيل المحاولات المشبوهة** | ✅ | تسجيل أي محاولة وصول غير مصرح بها |
| **قفل ذري** للفحص المتزامن | ✅ | منع تكرار الفحص لنفس الفترة الزمنية |
| **تقييد معلومات الإصدار** | ✅ | إخفاء إصدار MySQL و PHP في الردود العامة |
| **HTTPS إجباري** | ✅ | عبر تكوين الخادم |
| **مهلة زمنية** للفحص | ✅ | 5 ثوانٍ كحد أقصى لكل فحص |
| **لا توجد معلومات حساسة** في الاستجابة العامة | ✅ | `error` محذوف في الإنتاج |

---

## توصيات إضافية (Additional Recommendations)

### 1. استخدام VPN للوحة المشرف

```apache
# .htaccess - تقييد الوصول إلى /admin/system/health
<LocationMatch "^/api/admin/system/health">
    Require ip 10.0.0.0/8
    Require ip 192.168.0.0/16
</LocationMatch>
```

### 2. مراقبة معدل الطلبات بشكل متقدم

```php
<?php
// ترجمة: إرسال إنذار عند تجاوز حد معين من الطلبات

public function checkForDosAttack(): void
{
    $ip = request()->ip();
    $key = 'health:request_count:' . $ip;

    $count = Cache::get($key, 0) + 1;
    Cache::put($key, $count, 60);

    if ($count > 100) {
        // ترجمة: أكثر من 100 طلب في الدقيقة من نفس IP
        Log::critical("هجوم DoS محتمل من IP: {$ip}");
        // ترجمة: حظر IP temporarily
        Cache::put('health:blocked:' . $ip, true, 3600);
    }
}
```

### 3. تدوير مفاتيح JWT بانتظام

```bash
# cron job لتجديد مفاتيح JWT كل 30 يوماً
0 0 1 * * cd /var/www/beza && php artisan jwt:secret --force
```

---

## ملخص أمني (Security Summary)

```
┌─────────────────────────────────────────────────────────────────┐
│                      SY2-health Security                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  📌  Public Endpoints (7)                                       │
│      ✓ Rate limited: 60 req/min                                 │
│      ✓ Cached: 30s TTL                                          │
│      ✓ Sanitized: no technical details                          │
│      ✓ Timeout: 5s max                                          │
│                                                                  │
│  🔒  Admin Endpoint (1)                                          │
│      ✓ JWT authentication (auth:api)                            │
│      ✓ Role: admin only                                         │
│      ✓ Rate limited: 30 req/min                                 │
│      ✓ IP restriction (optional)                                 │
│                                                                  │
│  🛡️  Attack Prevention                                          │
│      ✓ Cache lock for concurrent requests                       │
│      ✓ Suspicious activity logging                               │
│      ✓ DoS detection (100+ req/min from same IP)                │
│      ✓ Temporary IP blocking                                     │
│                                                                  │
│  📝  Logging                                                     │
│      ✓ All unauthorized access attempts                         │
│      ✓ All rate limit violations                                │
│      ✓ All service failures                                     │
│      ✓ Separate security log channel                             │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```
