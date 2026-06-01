# 10 - CacheService للوحة التحكم

```php
<?php
// app/Services/Admin/CacheService.php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * الحصول من Cache أو التنفيذ والتخزين
     */
    public function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        $start = microtime(true);

        $value = Cache::remember($key, $ttlSeconds, $callback);

        $duration = (microtime(true) - $start) * 1000;

        $this->logAccess($key, $value !== null, $duration);

        return $value;
    }

    /**
     * الحصول من Cache فقط (بدون fallback)
     */
    public function get(string $key): mixed
    {
        return Cache::get($key);
    }

    /**
     * تخزين في Cache مع TTL
     */
    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        Cache::put($key, $value, $ttlSeconds);
    }

    /**
     * حذف Cache
     */
    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * حذف مجموعة من Cache حسب pattern
     */
    public function forgetByPattern(string $pattern): void
    {
        // Redis: مسح باستخدام pattern
        // foreach (Redis::keys($pattern) as $key) {
        //     Cache::forget($key);
        // }
    }

    /**
     * التحقق من وجود Cache
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * تسجيل الوصول إلى Cache
     */
    private function logAccess(string $key, bool $hit, float $durationMs): void
    {
        $status = $hit ? 'HIT' : 'MISS';

        Log::debug("Cache {$status}: {$key} ({$durationMs}ms)");

        // إرسال إلى نظام مراقبة (مستقبلاً)
        // StatsD::increment("cache.{$status}");
        // StatsD::timing("cache.duration", $durationMs);
    }

    /**
     * معدل الـ Hit Rate
     */
    public function getHitRate(): array
    {
        // تنفيذ: استخدام Cache::tags() مع Redis للتخزين المؤقت المتقدم
        return [
            'hits'   => 0,
            'misses' => 0,
            'rate'   => 0,
        ];
    }
}
```

## استراتيجية التخزين المؤقت

| المفتاح | TTL | الوصف |
|---------|-----|-------|
| `dashboard_stats` | 300s | جميع إحصائيات لوحة التحكم |
| `dashboard_revenue_30d` | 600s | بيانات الإيرادات آخر 30 يوم |
| `dashboard_volume_30d` | 600s | بيانات حجم المعاملات |
| `dashboard_growth_30d` | 600s | بيانات نمو المستخدمين |
| `top_merchants` | 900s | أعلى التجار (15 دقيقة) |

## إعادة المحاولة عند فشل Cache

```php
// إذا فشل Redis Cache, نقرأ من DB مباشرة
try {
    $data = Cache::get(self::CACHE_KEY);
} catch (\RedisException $e) {
    Log::error('Redis unavailable, falling back to DB', [
        'error' => $e->getMessage(),
    ]);
    $data = $this->generateStats($period);
}
```
