# 19 - حالات الحافة (Edge Cases)

**الرمز التشغيلي:** SY2-health  
**النوع:** توثيق حالات الحافة (Edge Cases Documentation)

---

## نظرة عامة (Overview)

نظام التحقق الصحي يجب أن يتعامل مع العديد من حالات الحافة. هذا الملف يوثق كل حالة وكيفية التعامل معها.

---

## حالة 1: Redis معطل بالكامل (Redis Completely Down)

### السيناريو

```
Redis Server: ✕ معطل
PHP Redis Extension: مثبت
التأثير: جميع العمليات التي تعتمد على Redis ستتعطل
```

### الاستجابة المتوقعة

```json
{
    "status": "degraded",
    "services": [
        {"name": "redis",     "status": "down", "error": "فشل الاتصال بـ Redis: Connection refused"},
        {"name": "cache",     "status": "down", "error": "فشل كتابة القيمة في الكاش"},
        {"name": "queue",     "status": "down", "error": "فشل الاتصال بقائمة الانتظار"},
        {"name": "database",  "status": "up"},
        {"name": "storage",   "status": "up"},
        {"name": "php",       "status": "up"}
    ]
}
```

### آلية التعامل

```php
<?php
// ترجمة: عند فشل Redis، نستمر في فحص باقي الخدمات

try {
    $redisResult = $redisChecker->check();
} catch (\Exception $e) {
    // ترجمة: Redis معطل - ننتقل للخدمة التالية
    $redisResult = HealthCheckResult::down('redis', 'Redis غير متاح');
}

try {
    $cacheResult = $cacheChecker->check();
} catch (\Exception $e) {
    // ترجمة: الكاش يعتمد على Redis - سيفشل أيضاً
    $cacheResult = HealthCheckResult::down('cache', 'الكاش غير متاح (Redis معطل)');
}

try {
    $queueResult = $queueChecker->check();
} catch (\Exception $e) {
    // ترجمة: قائمة الانتظار تستخدم Redis - ستفشل
    $queueResult = HealthCheckResult::down('queue', 'قائمة الانتظار غير متاحة (Redis معطل)');
}

// ترجمة: لكن قاعدة البيانات والتخزين و PHP لا يزالون يعملون
$databaseResult = $databaseChecker->check(); // ← يعمل
$storageResult = $storageChecker->check();    // ← يعمل
$phpResult = $requirementsChecker->check();   // ← يعمل
```

**ملاحظة:** حتى مع تعطل 3 خدمات، النظام لا يزال يقدم تقريراً كاملاً للخدمات الثلاث العاملة.

---

## حالة 2: MySQL بطيء جداً (MySQL Very Slow)

### السيناريو

```
MySQL Server: يعمل لكن بطيء
زمن الاستعلام: 3-5 ثوانٍ (بدلاً من < 10ms)
السبب: استعلامات ثقيلة، قفل جداول، مشاكل في IO
```

### آلية التعامل

```php
<?php
// ترجمة: كشف بطء MySQL

$startTime = microtime(true);

try {
    // ترجمة: نستخدم مهلة زمنية للاستعلام
    $connection = DB::connection();
    $connection->setPdo(new \PDO(
        'mysql:host=...;dbname=...',
        'user',
        'pass',
        [
            \PDO::ATTR_TIMEOUT => 2, // 2 ثانية مهلة
        ]
    ));

    $result = DB::select('SELECT 1');
    $latencyMs = (microtime(true) - $startTime) * 1000;

    if ($latencyMs > 1000) {
        // ترجمة: زمن استجابة أكبر من ثانية ← تحذير
        return HealthCheckResult::degraded(
            'database',
            $latencyMs,
            "قاعدة البيانات بطيئة: {$latencyMs}ms"
        );
    }

    return HealthCheckResult::up('database', $latencyMs);

} catch (\Exception $e) {
    // ترجمة: انتهت المهلة أو فشل الاتصال
    return HealthCheckResult::down('database', 'انتهت مهلة الاتصال: ' . $e->getMessage());
}
```

**النتيجة:** `database: degraded` مع تحذير بزمن الاستجابة.

---

## حالة 3: القرص ممتلئ (Disk Full)

### السيناريو

```
المساحة المستخدمة: 97%
المساحة المتبقية: 500 MB
الخطر: توقف كتابة السجلات، فشل رفع الملفات، تعطل التطبيق
```

### آلية التعامل

```php
<?php
// ترجمة: كشف امتلاء القرص

$path = storage_path();
$freeSpace = disk_free_space($path);
$totalSpace = disk_total_space($path);
$usagePercent = 100 - (($freeSpace / $totalSpace) * 100);

$warningThreshold = config('health.disk_warning_percent', 90);
$criticalThreshold = 98;

if ($usagePercent >= $criticalThreshold) {
    // ترجمة: قرص ممتلئ بشكل خطير
    return HealthCheckResult::down('storage', "القرص ممتلئ تقريباً: {$usagePercent}%");
}

if ($usagePercent >= $warningThreshold) {
    // ترجمة: قرص يقترب من الامتلاء
    return HealthCheckResult::degraded('storage', 'مساحة القرص منخفضة: ' . round($usagePercent, 1) . '%');
}

// ترجمة: مساحة كافية
return HealthCheckResult::up('storage', 0, [
    'disk_usage_percent' => round($usagePercent, 1),
]);
```

**النتيجة:** إذا زاد الاستهلاك عن 90% ← `storage: degraded`. إذا زاد عن 98% ← `storage: down`.

---

## حالة 4: جميع الخدمات معطلة (All Services Down)

### السيناريو

```
الموقف: انقطاع كامل للتيار الكهربائي أو مشكلة في الشبكة
النتيجة: MySQL ✕ | Redis ✕ | Cache ✕ | Queue ✕ | Storage degraded | PHP ✓
```

### الاستجابة المتوقعة

```json
{
    "status": "down",
    "services": [
        {"name": "database",     "status": "down", "error": "فشل الاتصال: Connection refused"},
        {"name": "redis",        "status": "down", "error": "فشل الاتصال: Network is unreachable"},
        {"name": "cache",        "status": "down", "error": "فشل تخزين القيمة في الكاش"},
        {"name": "queue",        "status": "down", "error": "فشل الاتصال: Connection refused"},
        {"name": "storage",      "status": "degraded", "warning": "مساحة القرص منخفضة: 95%"},
        {"name": "php",          "status": "up"}
    ],
    "timestamp": "2026-05-27T10:30:00Z"
}
```

**ملاحظة مهمة:** حتى في أسوأ السيناريوهات، مدقق PHP يظل يعمل لأنه لا يعتمد على خدمات خارجية. هذا يضمن أن النظام قادر على إرجاع تقرير حتى في حالة انقطاع شامل.

---

## حالة 5: انتهاء مهلة الفحص (Check Timeout)

### السيناريو

```
طلب فحص صحي ← استغرق أكثر من 5 ثوانٍ
السبب: إحدى الخدمات لا تستجيب (hanging connection)
```

### آلية التعامل

```php
<?php
// ترجمة: تنفيذ الفحص مع مهلة زمنية

use App\Services\Health\Exceptions\HealthCheckTimeoutException;

$timeout = config('health.timeout', 5); // 5 ثوانٍ كحد أقصى

try {
    // ترجمة: استخدام coroutine أو عملية منفصلة للمهلة
    $result = retry(1, function () use ($checker) {
        return $checker->check();
    }, 100);
} catch (\Exception $e) {
    // ترجمة: إذا انتهت المهلة، نعيد down
    $result = new HealthCheckTimeoutException($serviceName, $timeout);
}
```

---

## حالة 6: مضاعفة الطلبات المتزامنة (Concurrent Requests)

### السيناريو

```
100 طلب متزامن لنقطة /system/health
بدون تخزين مؤقت: 100 طلب × 6 فحوصات = 600 عملية على الخدمات
مع تخزين مؤقت 30 ثانية: طلب واحد فقط يصل إلى الخدمات
```

### آلية التعامل

```php
<?php
// ترجمة: التخزين المؤقت يمنع هجمات DoS

// الطلب الأول: يفحص جميع الخدمات ويخزن النتيجة لمدة 30 ثانية
$report = $this->healthService->getGeneralHealthReport();
Cache::put('health:general', $report, 30);

// الطلبات 2-100: ترجع النتيجة المخزنة خلال 30 ثانية
$cached = Cache::get('health:general');
return $cached; // لا يتم فحص الخدمات مرة أخرى
```

---

## حالة 7: إضافة PHP مفقودة (Missing PHP Extension)

### السيناريو

```
بعد تحديث PHP، تم فقدان إضافة mbstring أو json
بعض وظائف التطبيق قد تتعطل، لكن التحقق الصحي يكتشف المشكلة فوراً
```

### آلية التعامل

```php
<?php
// ترجمة: فحص الإضافات بدقة

$requiredExtensions = ['pdo', 'mbstring', 'json', 'redis'];
$missing = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}

if (!empty($missing)) {
    // ترجمة: إضافات مفقودة - تدهور وليس عطل كامل
    return HealthCheckResult::degraded('php_requirements', 0, [
        'missing' => $missing,
        'warning' => 'إضافات PHP مفقودة: ' . implode(', ', $missing),
    ]);
}
```

---

## حالة 8: النظام يشتغل على Windows (Windows Environment)

### السيناريو

```
بيئة تطوير على Windows
دالة /proc/uptime غير موجودة
بعض مسارات المجلدات تختلف
```

### آلية التعامل

```php
<?php
// ترجمة: التعامل مع Windows في دالة uptime

protected function getUptime(): ?string
{
    if (PHP_OS_FAMILY === 'Windows') {
        // ترجمة: Windows لا يدعم /proc/uptime
        // نستخدم wmic كبديل
        try {
            $output = shell_exec('wmic os get lastbootuptimemanagement');
            return 'راجع مدير المهام لمدة التشغيل';
        } catch (\Exception $e) {
            return 'غير متاح على Windows';
        }
    }

    // ترجمة: Linux - قراءة من /proc/uptime
    $uptime = @file_get_contents('/proc/uptime');
    if ($uptime !== false) {
        $seconds = (float) explode(' ', $uptime)[0];
        return gmdate('H:i:s', $seconds);
    }

    return null;
}
```

---

## جدول ملخص حالات الحافة (Edge Cases Summary)

| الحالة (Case) | الخدمات المتأثرة | الحالة المتوقعة | الإجراء (Action) |
|--------------|-----------------|----------------|-----------------|
| Redis معطل | redis, cache, queue | 3 خدمات down | إشعار فوري للفريق |
| MySQL بطيء | database | degraded مع تحذير | تسجيل في السجل |
| قرص ممتلئ | storage | degraded أو down | إشعار بمساحة القرص |
| جميع الخدمات معطلة | 5 من 6 | down | إشعار عاجل |
| مهلة فحص | الخدمة البطيئة | down | تسجيل الخطأ |
| طلبات متزامنة | لا يوجد | cached response | منع DoS تلقائياً |
| إضافة PHP مفقودة | php_requirements | degraded | تسجيل في السجل |
| بيئة Windows | uptime | null/رسالة | تجاهل |
