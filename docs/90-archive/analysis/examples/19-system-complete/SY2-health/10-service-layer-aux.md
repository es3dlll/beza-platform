# 10 - طبقة الخدمات المساعدة (Auxiliary Service Layer)

**الرمز التشغيلي:** SY2-health  
**النوع:** كود خدمات مساعدة (Auxiliary Service Code)

---

## HealthResponseFormatter - تنسيق الاستجابة

```php
<?php

namespace App\Services\Health;

use App\Services\Health\ValueObjects\HealthCheckResult;
use App\Services\Health\ValueObjects\HealthReport;
use Illuminate\Support\Facades\Log;

/**
 * منسق استجابات التحقق الصحي
 * مسؤول عن تنسيق النتائج بشكل موحد قبل الإرجاع
 */
class HealthResponseFormatter
{
    /**
     * تنسيق نتيجة فحص واحد
     *
     * @param HealthCheckResult $result
     * @param bool $includeDetails هل تشمل التفاصيل
     * @return array
     */
    public function formatSingleResult(HealthCheckResult $result, bool $includeDetails = true): array
    {
        // ترجمة: بناء الهيكل الأساسي للاستجابة
        $response = [
            'status'     => $result->status,
            'service'    => $result->name,
            'latency_ms' => $result->latency_ms,
        ];

        // ترجمة: إضافة التفاصيل إذا طلب ذلك
        if ($includeDetails && !empty($result->details)) {
            $response['details'] = $result->details;
        }

        // ترجمة: إضافة رسالة الخطأ إذا وجدت
        if ($result->error !== null) {
            $response['error'] = $result->error;
        }

        // ترجمة: إضافة الطابع الزمني
        $response['timestamp'] = now()->toIso8601String();

        return $response;
    }

    /**
     * تنسيق تقرير صحي كامل
     *
     * @param HealthReport $report
     * @return array
     */
    public function formatReport(HealthReport $report): array
    {
        // ترجمة: تحويل جميع النتائج إلى مصفوفات
        $services = array_map(
            fn(HealthCheckResult $result) => $this->formatSingleResult($result),
            $report->results
        );

        // ترجمة: بناء التقرير النهائي
        return [
            'status'    => $report->status,
            'services'  => $services,
            'timestamp' => now()->toIso8601String(),
            'cached'    => $report->cached,
        ];
    }

    /**
     * تنسيق استجابة خطأ موحدة
     *
     * @param string $message
     * @param int $statusCode
     * @return array
     */
    public function formatError(string $message, int $statusCode = 500): array
    {
        // ترجمة: تنسيق رسالة خطأ بشكل موحد
        return [
            'status'    => 'error',
            'message'   => $message,
            'code'      => $statusCode,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * تحديد الحالة العامة من مجموعة نتائج
     *
     * @param array $results
     * @return string ok|degraded|down
     */
    public function determineOverallStatus(array $results): string
    {
        // ترجمة: تحديد أسوأ حالة بين جميع النتائج
        $status = 'ok';

        foreach ($results as $result) {
            if ($result->status === 'down') {
                // ترجمة: وجود خدمة معطلة يعني حالة عامة down
                return 'down';
            }

            if ($result->status === 'degraded') {
                // ترجمة: وجود خدمة متدهورة يعني حالة عامة degraded
                $status = 'degraded';
            }
        }

        return $status;
    }

    /**
     * تقليل حجم الاستجابة للإنتاج
     * يزيل التفاصيل غير الضرورية للطلبات العامة
     *
     * @param array $fullResponse
     * @return array
     */
    public function minimizeResponse(array $fullResponse): array
    {
        // ترجمة: للاستخدام في بيئة الإنتاج، نقلل حجم الاستجابة
        $minimized = [
            'status'    => $fullResponse['status'] ?? 'unknown',
            'services'  => [],
            'timestamp' => $fullResponse['timestamp'] ?? now()->toIso8601String(),
        ];

        // ترجمة: تبسيط معلومات كل خدمة
        foreach ($fullResponse['services'] ?? [] as $service) {
            $minimized['services'][] = [
                'name'       => $service['name'],
                'status'     => $service['status'],
                'latency_ms' => $service['latency_ms'] ?? 0,
            ];
        }

        return $minimized;
    }
}
```

---

## HealthCacheLayer - طبقة التخزين المؤقت

```php
<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * طبقة التخزين المؤقت لنتائج التحقق الصحي
 * تمنع هجمات DoS وتقلل الضغط على الخدمات
 */
class HealthCacheLayer
{
    /**
     * البادئة المستخدمة لمفاتيح الكاش
     */
    const CACHE_PREFIX = 'health:';

    /**
     * مدة التخزين المؤقت الافتراضية بالثواني
     *
     * @var int
     */
    protected int $defaultTtl;

    /**
     * إنشاء طبقة الكاش مع الإعدادات
     */
    public function __construct()
    {
        // ترجمة: قراءة مدة التخزين المؤقت من الإعدادات
        $this->defaultTtl = config('health.cache_ttl', 30);
    }

    /**
     * الحصول على نتائج مخزنة مؤقتاً
     *
     * @param string $key مفتاح الكاش (بدون بادئة)
     * @return array|null
     */
    public function get(string $key): ?array
    {
        // ترجمة: بناء المفتاح الكامل مع البادئة
        $cacheKey = self::CACHE_PREFIX . $key;

        // ترجمة: محاولة جلب القيمة من الكاش
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            // ترجمة: تم العثور على نتائج مخزنة، نضيف علامة cached
            $cached['cached'] = true;
            Log::debug("نتائج التحقق الصحي مسترجعة من الكاش: {$cacheKey}");
        }

        return $cached;
    }

    /**
     * تخزين النتائج في الكاش
     *
     * @param string $key مفتاح الكاش
     * @param array $data البيانات المراد تخزينها
     * @param int|null $ttl مدة التخزين (تستخدم القيمة الافتراضية إذا كانت null)
     * @return bool
     */
    public function put(string $key, array $data, ?int $ttl = null): bool
    {
        // ترجمة: بناء المفتاح الكامل وتحديد مدة التخزين
        $cacheKey = self::CACHE_PREFIX . $key;
        $ttl = $ttl ?? $this->defaultTtl;

        // ترجمة: التأكد من عدم وجود علامة cached في البيانات المخزنة
        unset($data['cached']);

        // ترجمة: تخزين البيانات
        $result = Cache::put($cacheKey, $data, $ttl);

        if ($result) {
            Log::debug("نتائج التحقق الصحي مخزنة: {$cacheKey} لمدة {$ttl} ثانية");
        } else {
            Log::warning("فشل تخزين نتائج التحقق الصحي: {$cacheKey}");
        }

        return $result;
    }

    /**
     * مسح نتائج مخزنة محددة
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        // ترجمة: مسح مفتاح معين من الكاش
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::forget($cacheKey);
    }

    /**
     * مسح جميع نتائج التحقق الصحي المخزنة
     *
     * @return bool
     */
    public function flush(): bool
    {
        // ترجمة: مسح جميع المفاتيح التي تبدأ بالبادئة health:
        // هذا يتطلب استخدام Cache::tags أو مسح يدوي
        // حالياً نعتمد على انتهاء الصلاحية التلقائي
        try {
            // ترجمة: في حالة استخدام Redis، يمكننا مسح المفاتيح
            if (config('cache.default') === 'redis') {
                $redis = \Illuminate\Support\Facades\Redis::connection();
                $keys = $redis->keys(self::CACHE_PREFIX . '*');

                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        Cache::forget($key);
                    }
                    Log::info('تم مسح جميع نتائج التحقق الصحي من الكاش');
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('فشل مسح نتائج التحقق الصحي: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * التحقق من وجود نتائج مخزنة
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        // ترجمة: التحقق من وجود المفتاح في الكاش
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::has($cacheKey);
    }

    /**
     * الحصول على مفتاح الكاش مع معلومات إضافية
     *
     * @param string $type نوع الفحص
     * @param int|null $userId معرف المستخدم (للفحص المخصص)
     * @return string
     */
    public function buildKey(string $type, ?int $userId = null): string
    {
        // ترجمة: بناء مفتاح كاش مناسب حسب نوع الفحص
        $key = $type;

        if ($userId !== null) {
            // ترجمة: إضافة معرف المستخدم للفحوصات المخصصة
            $key .= ":user:{$userId}";
        }

        return $key;
    }
}
```

---

## HealthEventDispatcher - موزع الأحداث

```php
<?php

namespace App\Services\Health;

use App\Events\HealthCheckCompleted;
use App\Events\HealthServiceDegraded;
use App\Events\HealthServiceDown;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * موزع أحداث التحقق الصحي
 * مسؤول عن إطلاق الأحداث المناسبة بناءً على نتائج الفحص
 */
class HealthEventDispatcher
{
    /**
     * إطلاق الأحداث بعد اكتمال الفحص
     *
     * @param array $results
     * @param string $overallStatus
     * @return void
     */
    public function dispatch(array $results, string $overallStatus): void
    {
        // ترجمة: إطلاق حدث اكتمال الفحص (دائماً)
        event(new HealthCheckCompleted($results, $overallStatus));
        Log::debug('تم إطلاق حدث HealthCheckCompleted');

        // ترجمة: فحص النتائج وإطلاق الأحداث المناسبة
        foreach ($results as $result) {
            if ($result['status'] === 'down') {
                // ترجمة: خدمة معطلة بالكامل
                event(new HealthServiceDown(
                    $result['name'],
                    $result['error'] ?? 'خدمة معطلة بدون تفاصيل'
                ));
                Log::warning("تم إطلاق حدث HealthServiceDown للخدمة: {$result['name']}");
            }

            if ($result['status'] === 'degraded') {
                // ترجمة: خدمة بحالة متدهورة
                event(new HealthServiceDegraded(
                    $result['name'],
                    $result['details']['warning'] ?? 'خدمة بحالة متدهورة'
                ));
                Log::info("تم إطلاق حدث HealthServiceDegraded للخدمة: {$result['name']}");
            }
        }
    }

    /**
     * إطلاق حدث عند شفاء خدمة (عودة للعمل)
     *
     * @param string $serviceName
     * @return void
     */
    public function dispatchRecovered(string $serviceName): void
    {
        // ترجمة: إطلاق حدث عودة الخدمة للعمل
        event(new \App\Events\HealthServiceRecovered($serviceName));
        Log::info("تم إطلاق حدث HealthServiceRecovered للخدمة: {$serviceName}");
    }
}
```

---

## ملخص الخدمات المساعدة (Auxiliary Services Summary)

| الخدمة (Service) | المسؤولية (Responsibility) |
|-----------------|--------------------------|
| `HealthResponseFormatter` | تنسيق موحد لجميع الاستجابات |
| `HealthCacheLayer` | إدارة التخزين المؤقت لنتائج الفحص |
| `HealthEventDispatcher` | إطلاق الأحداث المناسبة بناءً على النتائج |

```
HealthService
    ├── DatabaseChecker
    ├── RedisChecker
    ├── CacheChecker
    ├── QueueChecker
    ├── RequirementsChecker
    ├── StorageChecker
    ├── HealthResponseFormatter  ← تنسيق الاستجابة
    ├── HealthCacheLayer         ← إدارة الكاش
    └── HealthEventDispatcher    ← إطلاق الأحداث
```
