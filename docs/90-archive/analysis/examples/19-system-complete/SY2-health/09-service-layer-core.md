# 09 - طبقة الخدمات الأساسية (Service Layer Core)

**الرمز التشغيلي:** SY2-health  
**النوع:** كود خدمة (Service Code)

---

## HealthService - الخدمة الرئيسية

```php
<?php

namespace App\Services\Health;

use App\Services\Health\Checkers\DatabaseChecker;
use App\Services\Health\Checkers\RedisChecker;
use App\Services\Health\Checkers\CacheChecker;
use App\Services\Health\Checkers\QueueChecker;
use App\Services\Health\Checkers\RequirementsChecker;
use App\Services\Health\Checkers\StorageChecker;
use App\Services\Health\ValueObjects\HealthCheckResult;
use App\Services\Health\ValueObjects\HealthReport;
use App\Services\Health\Exceptions\HealthCheckException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * خدمة التحقق الصحي الرئيسية
 * تدير جميع المدققين وتجمع النتائج
 */
class HealthService
{
    /**
     * المدققون المتاحون
     *
     * @var array
     */
    protected array $checkers = [];

    /**
     * مدة التخزين المؤقت بالثواني
     *
     * @var int
     */
    protected int $cacheTtl;

    /**
     * إنشاء الخدمة مع جميع المدققين
     *
     * @param DatabaseChecker $databaseChecker
     * @param RedisChecker $redisChecker
     * @param CacheChecker $cacheChecker
     * @param QueueChecker $queueChecker
     * @param RequirementsChecker $requirementsChecker
     * @param StorageChecker $storageChecker
     */
    public function __construct(
        protected DatabaseChecker     $databaseChecker,
        protected RedisChecker        $redisChecker,
        protected CacheChecker        $cacheChecker,
        protected QueueChecker        $queueChecker,
        protected RequirementsChecker $requirementsChecker,
        protected StorageChecker      $storageChecker,
    ) {
        // ترجمة: الحصول على مدة التخزين المؤقت من الإعدادات
        $this->cacheTtl = config('health.cache_ttl', 30);

        // ترجمة: تجهيز قائمة المدققين
        $this->checkers = [
            'database'     => $databaseChecker,
            'redis'        => $redisChecker,
            'cache'        => $cacheChecker,
            'queue'        => $queueChecker,
            'storage'      => $storageChecker,
            'php'          => $requirementsChecker,
        ];
    }

    /**
     * الحصول على التقرير الصحي العام
     * يستخدم التخزين المؤقت لمدة 30 ثانية
     *
     * @return array
     */
    public function getGeneralHealthReport(): array
    {
        // ترجمة: مفتاح التخزين المؤقت للتقرير العام
        $cacheKey = 'health:general';

        // ترجمة: محاولة جلب النتائج من الكاش
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            // ترجمة: تم العثور على نتائج مخزنة
            $cached['cached'] = true;
            return $cached;
        }

        // ترجمة: لا توجد نتائج مخزنة، نقوم بفحص جميع الخدمات
        $results = [];
        $overallStatus = 'ok';

        foreach ($this->checkers as $name => $checker) {
            try {
                // ترجمة: فحص الخدمة الحالية
                $result = $checker->check();
                $results[] = $result->toArray();

                // ترجمة: تحديث الحالة العامة حسب أسوأ حالة
                if ($result->status === 'down') {
                    $overallStatus = 'down';
                } elseif ($result->status === 'degraded' && $overallStatus !== 'down') {
                    $overallStatus = 'degraded';
                }

            } catch (\Exception $e) {
                // ترجمة: في حالة خطأ غير متوقع، نعتبر الخدمة معطلة
                Log::error("فشل فحص الخدمة: {$name}", [
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'name'       => $name,
                    'status'     => 'down',
                    'latency_ms' => 0,
                    'error'      => 'خطأ غير متوقع: ' . $e->getMessage(),
                ];

                $overallStatus = 'down';
            }
        }

        // ترجمة: تجهيز الاستجابة النهائية
        $report = [
            'status'    => $overallStatus,
            'services'  => $results,
            'timestamp' => now()->toIso8601String(),
            'cached'    => false,
        ];

        // ترجمة: تخزين النتيجة في الكاش
        Cache::put($cacheKey, $report, $this->cacheTtl);

        return $report;
    }

    /**
     * الحصول على تقرير مفصل للمشرفين
     * يشمل معلومات إضافية (بدون تخزين مؤقت)
     *
     * @return array
     */
    public function getDetailedReport(): array
    {
        // ترجمة: هذا التقرير لا يتم تخزينه مؤقتاً لأسباب أمنية
        $results = [];

        foreach ($this->checkers as $name => $checker) {
            try {
                $result = $checker->check();
                $data = $result->toArray();

                // ترجمة: إضافة تفاصيل إضافية للتقرير المفصل
                if ($name === 'database') {
                    $data['details']['query_time'] = $result->latency_ms;
                    $data['details']['server_info'] = $result->details['server'] ?? null;
                }

                if ($name === 'storage') {
                    $data['details']['disk_free'] = $result->details['disk_free'] ?? null;
                    $data['details']['disk_total'] = $result->details['disk_total'] ?? null;
                    $data['details']['disk_usage_percent'] = $result->details['disk_usage_percent'] ?? null;
                }

                $results[] = $data;

            } catch (\Exception $e) {
                $results[] = [
                    'name'       => $name,
                    'status'     => 'down',
                    'latency_ms' => 0,
                    'error'      => $e->getMessage(),
                ];
            }
        }

        // ترجمة: إضافة معلومات النظام العامة
        $systemInfo = [
            'php_version'    => PHP_VERSION,
            'laravel_version'=> app()->version(),
            'os'             => PHP_OS,
            'memory_usage'   => $this->getMemoryUsage(),
            'uptime'         => $this->getUptime(),
        ];

        $overallStatus = collect($results)
            ->reduce(function ($carry, $service) {
                if ($service['status'] === 'down') return 'down';
                if ($service['status'] === 'degraded' && $carry !== 'down') return 'degraded';
                return $carry;
            }, 'ok');

        return [
            'status'    => $overallStatus,
            'services'  => $results,
            'system'    => $systemInfo,
            'timestamp' => now()->toIso8601String(),
            'cached'    => false,
        ];
    }

    /**
     * فحص خدمة فردية
     *
     * @param string $serviceName
     * @return HealthCheckResult
     */
    public function checkService(string $serviceName): HealthCheckResult
    {
        // ترجمة: التحقق من وجود المدقق المطلوب
        if (!isset($this->checkers[$serviceName])) {
            throw new HealthCheckException("خدمة غير موجودة: {$serviceName}");
        }

        // ترجمة: تشغيل الفحص
        return $this->checkers[$serviceName]->check();
    }

    /**
     * فحص قاعدة البيانات
     *
     * @return HealthCheckResult
     */
    public function checkDatabase(): HealthCheckResult
    {
        return $this->databaseChecker->check();
    }

    /**
     * فحص Redis
     *
     * @return HealthCheckResult
     */
    public function checkRedis(): HealthCheckResult
    {
        return $this->redisChecker->check();
    }

    /**
     * فحص الكاش
     *
     * @return HealthCheckResult
     */
    public function checkCache(): HealthCheckResult
    {
        return $this->cacheChecker->check();
    }

    /**
     * فحص قائمة الانتظار
     *
     * @return HealthCheckResult
     */
    public function checkQueue(): HealthCheckResult
    {
        return $this->queueChecker->check();
    }

    /**
     * فحص متطلبات PHP
     *
     * @return HealthCheckResult
     */
    public function checkRequirements(): HealthCheckResult
    {
        return $this->requirementsChecker->check();
    }

    /**
     * فحص التخزين
     *
     * @return HealthCheckResult
     */
    public function checkStorage(): HealthCheckResult
    {
        return $this->storageChecker->check();
    }

    /**
     * الحصول على استخدام الذاكرة
     *
     * @return array
     */
    protected function getMemoryUsage(): array
    {
        // ترجمة: قراءة使用 الذاكرة من النظام
        $memoryLimit = ini_get('memory_limit');

        return [
            'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_usage_mb'    => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit'     => $memoryLimit,
        ];
    }

    /**
     * الحصول على مدة تشغيل النظام
     *
     * @return string|null
     */
    protected function getUptime(): ?string
    {
        // ترجمة: محاولة قراءة uptime من النظام (Linux فقط)
        if (PHP_OS_FAMILY === 'Windows') {
            return 'متاح على Linux فقط';
        }

        try {
            $uptime = file_get_contents('/proc/uptime');
            if ($uptime !== false) {
                $seconds = (float)explode(' ', $uptime)[0];
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);

                return "{$days} يوم، {$hours} ساعة";
            }
        } catch (\Exception $e) {
            // ترجمة: لا يمكن قراءة uptime
        }

        return null;
    }
}
```

---

## DatabaseChecker - مدقق قاعدة البيانات

```php
<?php

namespace App\Services\Health\Checkers;

use App\Services\Health\ValueObjects\HealthCheckResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * مدقق قاعدة بيانات MySQL
 * يفحص الاتصال ويقيس زمن الاستعلام
 */
class DatabaseChecker
{
    /**
     * تنفيذ فحص قاعدة البيانات
     *
     * @return HealthCheckResult
     */
    public function check(): HealthCheckResult
    {
        // ترجمة: تسجيل بداية الفحص لقياس الزمن
        $startTime = microtime(true);

        try {
            // ترجمة: تنفيذ استعلام بسيط لاختبار الاتصال
            $result = DB::select('SELECT 1 AS health_check');

            // ترجمة: حساب زمن الاستجابة
            $latencyMs = (microtime(true) - $startTime) * 1000;

            // ترجمة: الحصول على معلومات إضافية عن قاعدة البيانات
            $serverInfo = null;
            try {
                $versionResult = DB::select('SELECT VERSION() AS version');
                $serverInfo = $versionResult[0]->version ?? null;
            } catch (\Exception $e) {
                // ترجمة: فشل الحصول على الإصدار، لكن الاتصال لا يزال ناجحاً
            }

            // ترجمة: التحقق من أن النتيجة صحيحة
            if (!empty($result) && $result[0]->health_check === 1) {
                return HealthCheckResult::up('database', $latencyMs, [
                    'server' => $serverInfo ?? 'MySQL',
                ]);
            }

            // ترجمة: نتيجة غير متوقعة من قاعدة البيانات
            return HealthCheckResult::degraded(
                'database',
                $latencyMs,
                'نتيجة غير متوقعة من الاستعلام'
            );

        } catch (\Exception $e) {
            // ترجمة: فشل الاتصال بقاعدة البيانات
            Log::error('فشل فحص قاعدة البيانات: ' . $e->getMessage());

            return HealthCheckResult::down(
                'database',
                'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage()
            );
        }
    }
}
```

---

## RedisChecker - مدقق Redis

```php
<?php

namespace App\Services\Health\Checkers;

use App\Services\Health\ValueObjects\HealthCheckResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * مدقق Redis
 * يفحص الاتصال عبر ping
 */
class RedisChecker
{
    /**
     * تنفيذ فحص Redis
     *
     * @return HealthCheckResult
     */
    public function check(): HealthCheckResult
    {
        // ترجمة: تسجيل بداية الفحص
        $startTime = microtime(true);

        try {
            // ترجمة: محاولة الاتصال بـ Redis عبر ping
            $connection = Redis::connection();
            $pingResult = $connection->ping();

            // ترجمة: حساب زمن الاستجابة
            $latencyMs = (microtime(true) - $startTime) * 1000;

            // ترجمة: التحقق من نتيجة ping
            // في بعض إصدارات Redis يعيد ping كائن أو نص
            $isConnected = (
                $pingResult === true ||
                $pingResult === 'PONG' ||
                $pingResult === 1 ||
                (is_string($pingResult) && strtoupper($pingResult) === 'PONG')
            );

            if ($isConnected) {
                return HealthCheckResult::up('redis', $latencyMs, [
                    'connection' => config('database.redis.default.host', '127.0.0.1'),
                    'port'       => config('database.redis.default.port', 6379),
                ]);
            }

            // ترجمة: ping لم يعد النتيجة المتوقعة
            return HealthCheckResult::degraded(
                'redis',
                $latencyMs,
                'Redis ping لم يعد PONG: ' . var_export($pingResult, true)
            );

        } catch (\Exception $e) {
            // ترجمة: فشل الاتصال بـ Redis
            Log::error('فشل فحص Redis: ' . $e->getMessage());

            return HealthCheckResult::down(
                'redis',
                'فشل الاتصال بـ Redis: ' . $e->getMessage()
            );
        }
    }
}
```

---

## CacheChecker - مدقق الذاكرة المؤقتة

```php
<?php

namespace App\Services\Health\Checkers;

use App\Services\Health\ValueObjects\HealthCheckResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * مدقق الذاكرة المؤقتة
 * يختبر كتابة وقراءة قيمة في الكاش
 */
class CacheChecker
{
    /**
     * المفتاح المستخدم لاختبار الكاش
     */
    const TEST_KEY = 'health:cache:test';

    /**
     * القيمة المستخدمة لاختبار الكاش
     */
    const TEST_VALUE = 'health_check_ok';

    /**
     * تنفيذ فحص الكاش
     *
     * @return HealthCheckResult
     */
    public function check(): HealthCheckResult
    {
        // ترجمة: تسجيل بداية الفحص
        $startTime = microtime(true);

        try {
            // ترجمة: محاولة كتابة قيمة في الكاش
            $writeSuccess = Cache::put(self::TEST_KEY, self::TEST_VALUE, 10);

            if (!$writeSuccess) {
                // ترجمة: فشلت عملية الكتابة
                return HealthCheckResult::down(
                    'cache',
                    'فشلت عملية كتابة القيمة في الكاش'
                );
            }

            // ترجمة: محاولة قراءة القيمة من الكاش
            $readValue = Cache::get(self::TEST_KEY);

            // ترجمة: حساب زمن الاستجابة
            $latencyMs = (microtime(true) - $startTime) * 1000;

            // ترجمة: التحقق من تطابق القيمة المقروءة
            if ($readValue === self::TEST_VALUE) {
                // ترجمة: تنظيف المفتاح التجريبي
                Cache::forget(self::TEST_KEY);

                return HealthCheckResult::up('cache', $latencyMs, [
                    'driver' => config('cache.default', 'file'),
                    'ttl'    => 10,
                ]);
            }

            // ترجمة: القيمة المقروءة لا تطابق القيمة المكتوبة
            Cache::forget(self::TEST_KEY);

            return HealthCheckResult::degraded(
                'cache',
                $latencyMs,
                'عدم تطابق القيمة المقروءة مع القيمة المكتوبة'
            );

        } catch (\Exception $e) {
            // ترجمة: فشل فحص الكاش
            Log::error('فشل فحص الكاش: ' . $e->getMessage());

            // ترجمة: محاولة تنظيف المفتاح التجريبي
            try {
                Cache::forget(self::TEST_KEY);
            } catch (\Exception $cleanupError) {
                // ترجمة: فشل التنظيف أيضاً، لكننا نتجاهل هذا الخطأ
            }

            return HealthCheckResult::down(
                'cache',
                'فشل فحص الذاكرة المؤقتة: ' . $e->getMessage()
            );
        }
    }
}
```

---

## QueueChecker - مدقق قائمة الانتظار

```php
<?php

namespace App\Services\Health\Checkers;

use App\Services\Health\ValueObjects\HealthCheckResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * مدقق قائمة الانتظار
 * يتحقق من اتصال خدمة قوائم الانتظار
 */
class QueueChecker
{
    /**
     * تنفيذ فحص قائمة الانتظار
     *
     * @return HealthCheckResult
     */
    public function check(): HealthCheckResult
    {
        // ترجمة: تسجيل بداية الفحص
        $startTime = microtime(true);

        try {
            // ترجمة: الحصول على تعريف driver queue الحالي
            $defaultDriver = Queue::getDefaultDriver();
            $connection = Queue::connection($defaultDriver);

            // ترجمة: محاولة الوصول إلى حجم queue كاختبار اتصال
            // في Redis، هذا يختبر الاتصال فعلياً
            $size = 0;
            try {
                $size = $connection->size();
            } catch (\Exception $e) {
                // ترجمة: بعض drivers لا تدعم size()، نعتبر الاتصال ناجحاً
            }

            // ترجمة: حساب زمن الاستجابة
            $latencyMs = (microtime(true) - $startTime) * 1000;

            return HealthCheckResult::up('queue', $latencyMs, [
                'driver' => $defaultDriver,
                'size'   => $size,
            ]);

        } catch (\Exception $e) {
            // ترجمة: فشل الاتصال بقائمة الانتظار
            Log::error('فشل فحص قائمة الانتظار: ' . $e->getMessage());

            return HealthCheckResult::down(
                'queue',
                'فشل الاتصال بقائمة الانتظار: ' . $e->getMessage()
            );
        }
    }
}
```

---

## RequirementsChecker - مدقق متطلبات PHP

```php
<?php

namespace App\Services\Health\Checkers;

use App\Services\Health\ValueObjects\HealthCheckResult;

/**
 * مدقق متطلبات PHP
 * يفحص إصدار PHP والإضافات المطلوبة
 */
class RequirementsChecker
{
    /**
     * قائمة الإضافات المطلوبة
     *
     * @var array
     */
    protected array $requiredExtensions;

    /**
     * أقل إصدار PHP مقبول
     *
     * @var string
     */
    protected string $minPhpVersion;

    /**
     * إنشاء المدقق مع الإعدادات
     */
    public function __construct()
    {
        // ترجمة: قراءة الإعدادات من ملف config/health.php
        $this->requiredExtensions = config('health.required_extensions', [
            'pdo', 'mbstring', 'json', 'openssl',
            'tokenizer', 'ctype', 'redis', 'bcmath',
            'xml', 'fileinfo', 'gd',
        ]);
        $this->minPhpVersion = config('health.min_php_version', '8.1.0');
    }

    /**
     * تنفيذ فحص متطلبات PHP
     *
     * @return HealthCheckResult
     */
    public function check(): HealthCheckResult
    {
        // ترجمة: فحص إصدار PHP
        $phpVersion = PHP_VERSION;
        $versionOk = version_compare($phpVersion, $this->minPhpVersion, '>=');

        // ترجمة: فحص الإضافات المطلوبة
        $extensions = [];
        $missingExtensions = [];
        $loadedExtensions = get_loaded_extensions();

        foreach ($this->requiredExtensions as $ext) {
            $isLoaded = in_array($ext, $loadedExtensions) || extension_loaded($ext);
            $extensions[$ext] = $isLoaded;

            if (!$isLoaded) {
                $missingExtensions[] = $ext;
            }
        }

        // ترجمة: تحديد الحالة العامة
        if (!$versionOk) {
            // ترجمة: إصدار PHP قديم
            return HealthCheckResult::degraded('php_requirements', 0, [
                'php_version'    => $phpVersion,
                'min_required'   => $this->minPhpVersion,
                'version_ok'     => false,
                'extensions'     => $extensions,
                'missing_count'  => count($missingExtensions),
                'missing'        => $missingExtensions,
                'warning'        => "إصدار PHP {$phpVersion} أقل من المطلوب {$this->minPhpVersion}",
            ]);
        }

        if (!empty($missingExtensions)) {
            // ترجمة: توجد إضافات مفقودة
            return HealthCheckResult::degraded('php_requirements', 0, [
                'php_version'    => $phpVersion,
                'min_required'   => $this->minPhpVersion,
                'version_ok'     => true,
                'extensions'     => $extensions,
                'missing_count'  => count($missingExtensions),
                'missing'        => $missingExtensions,
                'warning'        => 'الإضافات المفقودة: ' . implode(', ', $missingExtensions),
            ]);
        }

        // ترجمة: كل شيء يعمل بشكل طبيعي
        return HealthCheckResult::up('php_requirements', 0, [
            'php_version'   => $phpVersion,
            'min_required'  => $this->minPhpVersion,
            'version_ok'    => true,
            'extensions'    => $extensions,
            'total_count'   => count($extensions),
        ]);
    }
}
```

---

## StorageChecker - مدقق التخزين

```php
<?php

namespace App\Services\Health\Checkers;

use App\Services\Health\ValueObjects\HealthCheckResult;
use Illuminate\Support\Facades\Log;

/**
 * مدقق التخزين
 * يفحص صلاحيات الكتابة للمجلدات المهمة
 */
class StorageChecker
{
    /**
     * المجلدات المراد فحصها
     *
     * @var array
     */
    protected array $directories;

    /**
     * النسبة المئوية للإنذار من مساحة القرص
     *
     * @var int
     */
    protected int $diskWarningPercent;

    /**
     * إنشاء المدقق مع الإعدادات
     */
    public function __construct()
    {
        // ترجمة: قراءة المجلدات من الإعدادات
        $this->directories = config('health.writable_directories', [
            storage_path('logs'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            public_path('uploads'),
        ]);

        $this->diskWarningPercent = config('health.disk_warning_percent', 90);
    }

    /**
     * تنفيذ فحص التخزين
     *
     * @return HealthCheckResult
     */
    public function check(): HealthCheckResult
    {
        // ترجمة: تسجيل بداية الفحص
        $startTime = microtime(true);

        try {
            // ترجمة: فحص كل مجلد
            $directoryStatus = [];
            $allWritable = true;
            $failedDirectories = [];

            foreach ($this->directories as $dir) {
                // ترجمة: التحقق من وجود المجلد
                if (!is_dir($dir)) {
                    // ترجمة: المجلد غير موجود، نحاول إنشائه
                    try {
                        @mkdir($dir, 0755, true);
                    } catch (\Exception $e) {
                        // ترجمة: لا يمكن إنشاء المجلد
                    }
                }

                // ترجمة: فحص صلاحية الكتابة
                $writable = is_writable($dir);
                $directoryStatus[] = [
                    'path'     => $dir,
                    'writable' => $writable,
                    'exists'   => is_dir($dir),
                ];

                if (!$writable) {
                    $allWritable = false;
                    $failedDirectories[] = $dir;
                }
            }

            // ترجمة: فحص مساحة القرص
            $diskInfo = $this->checkDiskSpace();

            // ترجمة: حساب زمن الاستجابة
            $latencyMs = (microtime(true) - $startTime) * 1000;

            // ترجمة: تحديد الحالة العامة
            $details = [
                'directories'           => $directoryStatus,
                'disk_usage'            => $diskInfo,
                'total_directories'     => count($this->directories),
                'writable_directories'  => count($this->directories) - count($failedDirectories),
                'failed_directories'    => $failedDirectories,
            ];

            if (!$allWritable) {
                return HealthCheckResult::degraded(
                    'storage',
                    $latencyMs,
                    'بعض المجلدات غير قابلة للكتابة: ' . implode(', ', $failedDirectories)
                );
            }

            if ($diskInfo && $diskInfo['usage_percent'] >= $this->diskWarningPercent) {
                return HealthCheckResult::degraded(
                    'storage',
                    $latencyMs,
                    "مساحة القرص تقترب من الامتلاء: {$diskInfo['usage_percent']}%"
                );
            }

            return HealthCheckResult::up('storage', $latencyMs, $details);

        } catch (\Exception $e) {
            // ترجمة: فشل فحص التخزين
            Log::error('فشل فحص التخزين: ' . $e->getMessage());

            return HealthCheckResult::down(
                'storage',
                'فشل فحص التخزين: ' . $e->getMessage()
            );
        }
    }

    /**
     * فحص مساحة القرص
     *
     * @return array|null
     */
    protected function checkDiskSpace(): ?array
    {
        // ترجمة: محاولة الحصول على معلومات مساحة القرص
        try {
            $path = storage_path();
            $freeSpace = @disk_free_space($path);
            $totalSpace = @disk_total_space($path);

            if ($freeSpace === false || $totalSpace === false) {
                return null;
            }

            $usedSpace = $totalSpace - $freeSpace;
            $usagePercent = round(($usedSpace / $totalSpace) * 100, 2);

            return [
                'free_bytes'    => $freeSpace,
                'total_bytes'   => $totalSpace,
                'used_bytes'    => $usedSpace,
                'free_gb'       => round($freeSpace / 1024 / 1024 / 1024, 2),
                'total_gb'      => round($totalSpace / 1024 / 1024 / 1024, 2),
                'used_gb'       => round($usedSpace / 1024 / 1024 / 1024, 2),
                'usage_percent' => $usagePercent,
            ];
        } catch (\Exception $e) {
            // ترجمة: فشل قراءة معلومات القرص
            return null;
        }
    }
}
```

---

## ملخص المدققين (Checkers Summary)

| المدقق (Checker) | الفحص (Check) | النجاح (Success) | الفشل (Failure) |
|-----------------|--------------|-----------------|----------------|
| `DatabaseChecker` | `SELECT 1` | `up` مع وقت الاستعلام | `down` مع رسالة الخطأ |
| `RedisChecker` | `ping()` | `up` مع معلومات الاتصال | `down` مع تفاصيل الخطأ |
| `CacheChecker` | `put + get` | `up` مع driver المستخدم | `down` إذا فشلت القراءة أو الكتابة |
| `QueueChecker` | `getDefaultDriver()` + `size()` | `up` مع driver | `down` مع رسالة الخطأ |
| `RequirementsChecker` | `version_compare()` + `extension_loaded()` | `up` أو `degraded` عند نقص إضافات | `degraded` فقط (ليس down) |
| `StorageChecker` | `is_writable()` + `disk_free_space()` | `up` أو `degraded` عند امتلاء القرص | `degraded` إذا بعض المجلدات غير قابلة للكتابة |
