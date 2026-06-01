# 13 - معالجة الاستثناءات (Exception Handling)

**الرمز التشغيلي:** SY2-health  
**النوع:** معالجة أخطاء (Exception Handling)

---

## فلسفة المعالجة (Handling Philosophy)

في نظام التحقق الصحي، **فشل خدمة لا يعني فشل الفحص بأكمله**. يجب أن يعمل النظام بتدهور تدريجي (graceful degradation): إذا كانت MySQL معطلة، نستمر في فحص Redis والخدمات الأخرى.

---

## استثناءات مخصصة (Custom Exceptions)

```php
<?php

namespace App\Services\Health\Exceptions;

use Exception;

/**
 * استثناء عام لنظام التحقق الصحي
 */
class HealthCheckException extends Exception
{
    /**
     * اسم الخدمة التي تسببت في الاستثناء
     *
     * @var string|null
     */
    protected ?string $serviceName = null;

    /**
     * @param string $message
     * @param int $code
     * @param string|null $serviceName
     */
    public function __construct(
        string $message = 'خطأ في التحقق الصحي',
        int $code = 0,
        ?string $serviceName = null,
        ?Exception $previous = null
    ) {
        // ترجمة: تمرير اسم الخدمة للمساعدة في التشخيص
        parent::__construct($message, $code, $previous);
        $this->serviceName = $serviceName;
    }

    /**
     * الحصول على اسم الخدمة المرتبطة بالخطأ
     *
     * @return string|null
     */
    public function getServiceName(): ?string
    {
        return $this->serviceName;
    }

    /**
     * تحويل الاستثناء إلى مصفوفة للتسجيل
     *
     * @return array
     */
    public function toLogContext(): array
    {
        return [
            'service' => $this->serviceName ?? 'unknown',
            'message' => $this->getMessage(),
            'code'    => $this->getCode(),
            'file'    => $this->getFile(),
            'line'    => $this->getLine(),
        ];
    }
}
```

---

## استثناءات متخصصة (Specialized Exceptions)

```php
<?php

namespace App\Services\Health\Exceptions;

/**
 * استثناء فشل اتصال قاعدة البيانات
 * يرمى عندما لا يمكن الاتصال بـ MySQL
 */
class DatabaseConnectionException extends HealthCheckException
{
    /**
     * @param string $message
     * @param Exception|null $previous
     */
    public function __construct(
        string $message = 'فشل الاتصال بقاعدة البيانات',
        ?Exception $previous = null
    ) {
        parent::__construct($message, 1001, 'database', $previous);
    }
}

/**
 * استثناء فشل اتصال Redis
 */
class RedisConnectionException extends HealthCheckException
{
    public function __construct(
        string $message = 'فشل الاتصال بـ Redis',
        ?Exception $previous = null
    ) {
        parent::__construct($message, 1002, 'redis', $previous);
    }
}

/**
 * استثناء فشل كتابة الكاش
 */
class CacheWriteException extends HealthCheckException
{
    public function __construct(
        string $message = 'فشل كتابة القيمة في الذاكرة المؤقتة',
        ?Exception $previous = null
    ) {
        parent::__construct($message, 1003, 'cache', $previous);
    }
}

/**
 * استثناء فشل اتصال قائمة الانتظار
 */
class QueueConnectionException extends HealthCheckException
{
    public function __construct(
        string $message = 'فشل الاتصال بقائمة الانتظار',
        ?Exception $previous = null
    ) {
        parent::__construct($message, 1004, 'queue', $previous);
    }
}

/**
 * استثناء فشل فحص التخزين
 */
class StorageCheckException extends HealthCheckException
{
    public function __construct(
        string $message = 'فشل فحص التخزين',
        ?Exception $previous = null
    ) {
        parent::__construct($message, 1005, 'storage', $previous);
    }
}

/**
 * استثناء مهلة الفحص
 * يرمى عندما يستغرق فحص خدمة وقتاً أطول من المسموح
 */
class HealthCheckTimeoutException extends HealthCheckException
{
    /**
     * المهلة المسموحة بالثواني
     *
     * @var int
     */
    protected int $timeout;

    /**
     * @param string $serviceName
     * @param int $timeout
     */
    public function __construct(string $serviceName, int $timeout)
    {
        $this->timeout = $timeout;
        parent::__construct(
            "انتهت مهلة فحص الخدمة: {$serviceName} بعد {$timeout} ثانية",
            1006,
            $serviceName
        );
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
```

---

## معالج الاستثناءات المركزي (Central Exception Handler)

```php
<?php

namespace App\Services\Health;

use App\Services\Health\Exceptions\HealthCheckException;
use App\Services\Health\Exceptions\HealthCheckTimeoutException;
use App\Services\Health\ValueObjects\HealthCheckResult;
use Illuminate\Support\Facades\Log;

/**
 * معالج الاستثناءات المركزي لنظام التحقق الصحي
 * يضمن التدهور التدريجي: فشل خدمة ≠ فشل الفحص الكامل
 */
class HealthExceptionHandler
{
    /**
     * معالجة استثناء أثناء فحص خدمة
     * لا يرمي الاستثناء، بل يعيد نتيجة فشل
     *
     * @param string $serviceName
     * @param \Exception $exception
     * @return HealthCheckResult
     */
    public function handle(string $serviceName, \Exception $exception): HealthCheckResult
    {
        // ترجمة: تسجيل الخطأ في السجل
        $this->logException($serviceName, $exception);

        // ترجمة: تحديد رسالة الخطأ المناسبة حسب نوع الاستثناء
        $errorMessage = $this->getErrorMessage($exception);

        // ترجمة: إرجاع نتيجة فشل بدون إيقاف الفحص
        return HealthCheckResult::down($serviceName, $errorMessage);
    }

    /**
     * معالجة استثناء أثناء فحص متطلبات PHP
     * يعيد حالة متدهورة وليس معطلة
     *
     * @param \Exception $exception
     * @return HealthCheckResult
     */
    public function handleRequirementsException(\Exception $exception): HealthCheckResult
    {
        // ترجمة: فشل فحص PHP يعتبر تدهوراً وليس عطلاً كاملاً
        $this->logException('php_requirements', $exception);

        return HealthCheckResult::degraded(
            'php_requirements',
            0,
            'تعذر فحص متطلبات PHP: ' . $exception->getMessage()
        );
    }

    /**
     * تسجيل الاستثناء في السجل مع معلومات إضافية
     *
     * @param string $serviceName
     * @param \Exception $exception
     * @return void
     */
    protected function logException(string $serviceName, \Exception $exception): void
    {
        // ترجمة: تجميع معلومات السياق
        $context = [
            'service' => $serviceName,
            'exception' => get_class($exception),
            'code'      => $exception->getCode(),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
        ];

        // ترجمة: إذا كان استثناءنا المخصص، نضيف معلومات إضافية
        if ($exception instanceof HealthCheckException) {
            $context['service_name'] = $exception->getServiceName();
        }

        // ترجمة: إذا كان استثناء مهلة، نضيف معلومات المهلة
        if ($exception instanceof HealthCheckTimeoutException) {
            $context['timeout'] = $exception->getTimeout();
        }

        // ترجمة: تسجيل حسب مستوى الخطورة
        if ($exception instanceof HealthCheckException) {
            Log::channel('health')->warning(
                "استثناء في فحص الخدمة: {$serviceName}",
                $context
            );
        } else {
            Log::channel('health')->error(
                "استثناء غير متوقع في فحص الخدمة: {$serviceName}",
                $context
            );
        }
    }

    /**
     * الحصول على رسالة خطأ مناسبة للمستخدم
     *
     * @param \Exception $exception
     * @return string
     */
    protected function getErrorMessage(\Exception $exception): string
    {
        // ترجمة: في بيئة الإنتاج، نعطي رسائل عامة
        if (app()->environment('production')) {
            return match (true) {
                $exception instanceof HealthCheckException => 'الخدمة غير متاحة حالياً',
                default => 'حدث خطأ غير متوقع أثناء فحص الخدمة',
            };
        }

        // ترجمة: في بيئة التطوير، نعطي رسائل تفصيلية
        return $exception->getMessage();
    }

    /**
     * محاولة استرداد الفحص بعد خطأ
     * تستخدم لبعض الخدمات التي قد تتعافى تلقائياً
     *
     * @param string $serviceName
     * @param callable $checkFunction
     * @param int $retries
     * @param int $delayMs
     * @return HealthCheckResult
     */
    public function retryWithFallback(
        string $serviceName,
        callable $checkFunction,
        int $retries = 1,
        int $delayMs = 100
    ): HealthCheckResult {
        // ترجمة: محاولة إعادة الفحص في حالة فشل مؤقت
        $lastException = null;

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                // ترجمة: محاولة تنفيذ الفحص
                return $checkFunction();

            } catch (\Exception $e) {
                // ترجمة: فشلت المحاولة
                $lastException = $e;

                if ($attempt < $retries) {
                    // ترجمة: انتظار قبل إعادة المحاولة
                    usleep($delayMs * 1000);
                    Log::debug(
                        "إعادة محاولة فحص {$serviceName}، المحاولة " .
                        ($attempt + 1) . " من {$retries}"
                    );
                }
            }
        }

        // ترجمة: جميع المحاولات فشلت
        return $this->handle($serviceName, $lastException);
    }
}
```

---

## مثال على التدهور التدريجي (Graceful Degradation Example)

```php
<?php

// في HealthService.php

use App\Services\Health\Exceptions\HealthCheckException;
use App\Services\Health\Exceptions\DatabaseConnectionException;

/**
 * فحص جميع الخدمات مع التدهور التدريجي
 */
public function checkAllServices(): array
{
    $results = [];
    $handler = app(HealthExceptionHandler::class);

    // ترجمة: فحص قاعدة البيانات
    try {
        $results[] = $this->databaseChecker->check();
    } catch (DatabaseConnectionException $e) {
        // ترجمة: MySQL معطل ← نسجل ونستمر
        $results[] = $handler->handle('database', $e);
    }

    // ترجمة: فحص Redis (حتى لو MySQL كان معطلاً)
    try {
        $results[] = $this->redisChecker->check();
    } catch (\Exception $e) {
        $results[] = $handler->handle('redis', $e);
    }

    // ترجمة: فحص الكاش
    try {
        $results[] = $this->cacheChecker->check();
    } catch (\Exception $e) {
        $results[] = $handler->handle('cache', $e);
    }

    // ترجمة: فحص قائمة الانتظار
    try {
        $results[] = $this->queueChecker->check();
    } catch (\Exception $e) {
        $results[] = $handler->handle('queue', $e);
    }

    // ترجمة: فحص التخزين
    try {
        $results[] = $this->storageChecker->check();
    } catch (\Exception $e) {
        $results[] = $handler->handle('storage', $e);
    }

    // ترجمة: فحص متطلبات PHP
    try {
        $results[] = $this->requirementsChecker->check();
    } catch (\Exception $e) {
        $results[] = $handler->handleRequirementsException($e);
    }

    return $results;
}
```

---

## مخطط تدفق معالجة الاستثناءات (Exception Handling Flow)

```
HealthService::getGeneralHealthReport()
    │
    ├── databaseChecker->check()
    │       ├── نجاح ← أضف النتيجة
    │       └── خطأ ← HealthExceptionHandler::handle('database', $e)
    │                   └── سجل الخطأ ← أعد نتيجة down
    │
    ├── redisChecker->check()
    │       ├── نجاح ← أضف النتيجة
    │       └── خطأ ← HealthExceptionHandler::handle('redis', $e)
    │
    ├── cacheChecker->check()
    │       ├── نجاح ← أضف النتيجة
    │       └── خطأ ← HealthExceptionHandler::handle('cache', $e)
    │
    ├── queueChecker->check()
    │       ├── نجاح ← أضف النتيجة
    │       └── خطأ ← HealthExceptionHandler::handle('queue', $e)
    │
    ├── storageChecker->check()
    │       ├── نجاح ← أضف النتيجة
    │       └── خطأ ← HealthExceptionHandler::handle('storage', $e)
    │
    └── requirementsChecker->check()
            ├── نجاح ← أضف النتيجة
            └── خطأ ← HealthExceptionHandler::handleRequirementsException($e)
```

---

## ملخص الاستثناءات (Exceptions Summary)

| الاستثناء (Exception) | الكود (Code) | الخدمة (Service) | متى يرمى (When) |
|---------------------|-------------|-----------------|----------------|
| `DatabaseConnectionException` | 1001 | database | فشل اتصال MySQL |
| `RedisConnectionException` | 1002 | redis | فشل اتصال Redis |
| `CacheWriteException` | 1003 | cache | فشل كتابة/قراءة الكاش |
| `QueueConnectionException` | 1004 | queue | فشل اتصال قائمة الانتظار |
| `StorageCheckException` | 1005 | storage | فشل فحص التخزين |
| `HealthCheckTimeoutException` | 1006 | أي خدمة | تجاوز المهلة الزمنية |
| `HealthCheckException` | 0 | عام | استثناء عام للفحص الصحي |

**القاعدة الذهبية:** لا تترك أي استثناء يصل إلى الكنترولر بدون معالجة. كل استثناء يتحول إلى نتيجة `down` أو `degraded` ولا يوقف الفحص الكلي.
