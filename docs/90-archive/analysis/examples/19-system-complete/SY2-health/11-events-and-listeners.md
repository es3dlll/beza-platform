# 11 - الأحداث والمستمعين (Events and Listeners)

**الرمز التشغيلي:** SY2-health  
**النوع:** نظام الأحداث (Event System)

---

## نظرة عامة (Overview)

نظام الأحداث في SY2-health يسمح بفصل منطق الفحص عن منطق المعالجة اللاحقة. عندما يكتمل فحص صحي، يتم إطلاق أحداث يمكن لمستمعين متعددين التعامل معها.

---

## الأحداث (Events)

### 1. HealthCheckCompleted

```php
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث اكتمال الفحص الصحي
 * يطلق بعد كل فحص صحي كامل
 */
class HealthCheckCompleted
{
    use Dispatchable, SerializesModels;

    /**
     * نتائج الفحص
     *
     * @var array
     */
    public array $results;

    /**
     * الحالة العامة (ok, degraded, down)
     *
     * @var string
     */
    public string $overallStatus;

    /**
     * وقت الفحص
     *
     * @var string
     */
    public string $checkedAt;

    /**
     * إنشاء حدث جديد
     *
     * @param array $results
     * @param string $overallStatus
     */
    public function __construct(array $results, string $overallStatus)
    {
        // ترجمة: تخزين نتائج الفحص والحالة العامة
        $this->results = $results;
        $this->overallStatus = $overallStatus;
        $this->checkedAt = now()->toIso8601String();
    }
}
```

### 2. HealthServiceDown

```php
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث تعطل خدمة
 * يطلق عندما تكون خدمة معينة معطلة بالكامل
 */
class HealthServiceDown
{
    use Dispatchable, SerializesModels;

    /**
     * اسم الخدمة المعطلة
     *
     * @var string
     */
    public string $serviceName;

    /**
     * رسالة الخطأ
     *
     * @var string
     */
    public string $errorMessage;

    /**
     * وقت اكتشاف العطل
     *
     * @var string
     */
    public string $detectedAt;

    /**
     * إنشاء حدث تعطل خدمة
     *
     * @param string $serviceName
     * @param string $errorMessage
     */
    public function __construct(string $serviceName, string $errorMessage)
    {
        // ترجمة: تخزين معلومات العطل
        $this->serviceName = $serviceName;
        $this->errorMessage = $errorMessage;
        $this->detectedAt = now()->toIso8601String();
    }
}
```

### 3. HealthServiceDegraded

```php
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث تدهور خدمة
 * يطلق عندما تكون خدمة تعمل ولكن بأداء متدهور
 */
class HealthServiceDegraded
{
    use Dispatchable, SerializesModels;

    /**
     * اسم الخدمة المتدهورة
     *
     * @var string
     */
    public string $serviceName;

    /**
     * رسالة التحذير
     *
     * @var string
     */
    public string $warningMessage;

    /**
     * وقت اكتشاف التدهور
     *
     * @var string
     */
    public string $detectedAt;

    /**
     * إنشاء حدث تدهور خدمة
     *
     * @param string $serviceName
     * @param string $warningMessage
     */
    public function __construct(string $serviceName, string $warningMessage)
    {
        // ترجمة: تخزين معلومات التدهور
        $this->serviceName = $serviceName;
        $this->warningMessage = $warningMessage;
        $this->detectedAt = now()->toIso8601String();
    }
}
```

### 4. HealthServiceRecovered

```php
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث عودة خدمة للعمل
 * يطلق عندما تعود خدمة معطلة للعمل بشكل طبيعي
 */
class HealthServiceRecovered
{
    use Dispatchable, SerializesModels;

    /**
     * اسم الخدمة التي عادت للعمل
     *
     * @var string
     */
    public string $serviceName;

    /**
     * وقت العودة
     *
     * @var string
     */
    public string $recoveredAt;

    /**
     * إنشاء حدث عودة للعمل
     *
     * @param string $serviceName
     */
    public function __construct(string $serviceName)
    {
        // ترجمة: تخزين معلومات العودة
        $this->serviceName = $serviceName;
        $this->recoveredAt = now()->toIso8601String();
    }
}
```

---

## المستمعون (Listeners)

### 1. LogHealthCheckResults

```php
<?php

namespace App\Listeners;

use App\Events\HealthCheckCompleted;
use Illuminate\Support\Facades\Log;

/**
 * تسجيل نتائج الفحص الصحي في ملف السجل
 */
class LogHealthCheckResults
{
    /**
     * معالجة حدث اكتمال الفحص
     *
     * @param HealthCheckCompleted $event
     * @return void
     */
    public function handle(HealthCheckCompleted $event): void
    {
        // ترجمة: تحديد مستوى السجل حسب الحالة العامة
        $logLevel = match ($event->overallStatus) {
            'down'     => 'error',      // ترجمة: خطأ - خدمة معطلة
            'degraded' => 'warning',    // ترجمة: تحذير - أداء متدهور
            default    => 'info',       // ترجمة: معلومات - كل شيء طبيعي
        };

        // ترجمة: تسجيل ملخص الفحص
        Log::channel('health')->$logLevel(
            'اكتمل الفحص الصحي: ' . $event->overallStatus,
            [
                'status'      => $event->overallStatus,
                'services'    => $event->results,
                'checked_at'  => $event->checkedAt,
            ]
        );

        // ترجمة: تسجيل كل خدمة على حدة
        foreach ($event->results as $service) {
            $serviceLogLevel = match ($service['status']) {
                'down'     => 'error',
                'degraded' => 'warning',
                default    => 'info',
            };

            Log::channel('health')->$serviceLogLevel(
                "الخدمة: {$service['name']} - الحالة: {$service['status']}",
                [
                    'service'    => $service['name'],
                    'status'     => $service['status'],
                    'latency_ms' => $service['latency_ms'] ?? 0,
                    'error'      => $service['error'] ?? null,
                ]
            );
        }
    }
}
```

### 2. AlertOnServiceDown

```php
<?php

namespace App\Listeners;

use App\Events\HealthServiceDown;
use App\Notifications\ServiceDownNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * إنذار عند تعطل خدمة
 * يرسل إشعاراً للفريق الفني
 */
class AlertOnServiceDown
{
    /**
     * قائمة المستلمين للإشعارات
     *
     * @var array
     */
    protected array $recipients;

    /**
     * إنشاء المستمع مع الإعدادات
     */
    public function __construct()
    {
        // ترجمة: قراءة قائمة المستلمين من الإعدادات
        $this->recipients = config('health.alert_recipients', []);
    }

    /**
     * معالجة حدث تعطل الخدمة
     *
     * @param HealthServiceDown $event
     * @return void
     */
    public function handle(HealthServiceDown $event): void
    {
        // ترجمة: تسجيل العطل في السجل
        Log::channel('health')->critical(
            "خدمة معطلة: {$event->serviceName}",
            [
                'service'      => $event->serviceName,
                'error'        => $event->errorMessage,
                'detected_at'  => $event->detectedAt,
            ]
        );

        // ترجمة: إرسال إشعار للفريق الفني إذا وجد مستلمون
        if (!empty($this->recipients)) {
            try {
                Notification::route('mail', $this->recipients)
                    ->notify(new ServiceDownNotification(
                        $event->serviceName,
                        $event->errorMessage,
                        $event->detectedAt
                    ));

                Log::info("تم إرسال إشعار تعطل الخدمة: {$event->serviceName}");
            } catch (\Exception $e) {
                Log::error(
                    "فشل إرسال إشعار تعطل الخدمة: {$e->getMessage()}"
                );
            }
        }
    }
}
```

### 3. TrackServiceDegradation

```php
<?php

namespace App\Listeners;

use App\Events\HealthServiceDegraded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * تتبع تدهور الخدمات
 * يسجل عدد مرات التدهور المتتالية
 */
class TrackServiceDegradation
{
    /**
     * مفتاح الكاش لتتبع التدهور
     */
    const COUNTER_PREFIX = 'health:degraded_counter:';

    /**
     * الحد الأقصى للتدهور المتتالي قبل رفع إنذار
     */
    const MAX_CONSECUTIVE_DEGRADATIONS = 3;

    /**
     * معالجة حدث تدهور الخدمة
     *
     * @param HealthServiceDegraded $event
     * @return void
     */
    public function handle(HealthServiceDegraded $event): void
    {
        // ترجمة: تسجيل التدهور في السجل
        Log::channel('health')->warning(
            "خدمة متدهورة: {$event->serviceName}",
            [
                'service' => $event->serviceName,
                'warning' => $event->warningMessage,
                'time'    => $event->detectedAt,
            ]
        );

        // ترجمة: زيادة عداد التدهور المتتالي
        $counterKey = self::COUNTER_PREFIX . $event->serviceName;
        $consecutiveCount = Cache::get($counterKey, 0) + 1;
        Cache::put($counterKey, $consecutiveCount, 3600);

        // ترجمة: إذا تكرر التدهور أكثر من الحد المسموح، نرفع إنذاراً
        if ($consecutiveCount >= self::MAX_CONSECUTIVE_DEGRADATIONS) {
            Log::channel('health')->critical(
                "تدهور متكرر للخدمة {$event->serviceName}" .
                " ({$consecutiveCount} مرات متتالية)"
            );
        }
    }
}
```

### 4. ResetDegradationCounter

```php
<?php

namespace App\Listeners;

use App\Events\HealthServiceRecovered;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * إعادة تعيين عداد التدهور عند عودة الخدمة للعمل
 */
class ResetDegradationCounter
{
    /**
     * معالجة حدث عودة الخدمة للعمل
     *
     * @param HealthServiceRecovered $event
     * @return void
     */
    public function handle(HealthServiceRecovered $event): void
    {
        // ترجمة: مسح عداد التدهور المتتالي
        $counterKey = TrackServiceDegradation::COUNTER_PREFIX . $event->serviceName;
        Cache::forget($counterKey);

        // ترجمة: تسجيل عودة الخدمة
        Log::channel('health')->info(
            "الخدمة عادت للعمل: {$event->serviceName}",
            [
                'service'      => $event->serviceName,
                'recovered_at' => $event->recoveredAt,
            ]
        );
    }
}
```

---

## تسجيل الأحداث والمستمعين (Event Service Provider)

```php
<?php

namespace App\Providers;

use App\Events\HealthCheckCompleted;
use App\Events\HealthServiceDegraded;
use App\Events\HealthServiceDown;
use App\Events\HealthServiceRecovered;
use App\Listeners\AlertOnServiceDown;
use App\Listeners\LogHealthCheckResults;
use App\Listeners\ResetDegradationCounter;
use App\Listeners\TrackServiceDegradation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * مزود خدمة الأحداث للتحقق الصحي
 */
class HealthEventServiceProvider extends ServiceProvider
{
    /**
     * ربط الأحداث بالمستمعين
     *
     * @var array
     */
    protected $listen = [
        // ترجمة: عند اكتمال الفحص الصحي
        HealthCheckCompleted::class => [
            LogHealthCheckResults::class,
        ],

        // ترجمة: عند تعطل خدمة
        HealthServiceDown::class => [
            AlertOnServiceDown::class,
        ],

        // ترجمة: عند تدهور خدمة
        HealthServiceDegraded::class => [
            TrackServiceDegradation::class,
        ],

        // ترجمة: عند عودة خدمة للعمل
        HealthServiceRecovered::class => [
            ResetDegradationCounter::class,
        ],
    ];

    /**
     * تسجيل أي خدمات إضافية
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();
    }
}
```

---

## مخطط الأحداث (Event Flow Diagram)

```
HealthService
    │
    ├── يفحص جميع الخدمات
    │
    ├── إطلاق HealthCheckCompleted
    │       └── LogHealthCheckResults ← يسجل النتائج
    │
    ├── لكل خدمة معطلة
    │       └── إطلاق HealthServiceDown
    │               └── AlertOnServiceDown ← يرسل إشعار
    │
    ├── لكل خدمة متدهورة
    │       └── إطلاق HealthServiceDegraded
    │               └── TrackServiceDegradation ← يتتبع التدهور
    │
    └── لكل خدمة عادت للعمل
            └── إطلاق HealthServiceRecovered
                    └── ResetDegradationCounter ← يعيد تعيين العداد
```

---

## ملخص الأحداث (Events Summary)

| الحدث (Event) | يطلق عندما (Triggered When) | المستمع (Listener) | الإجراء (Action) |
|--------------|---------------------------|-------------------|----------------|
| `HealthCheckCompleted` | اكتمال أي فحص صحي | `LogHealthCheckResults` | تسجيل النتائج في ملف السجل |
| `HealthServiceDown` | خدمة معطلة بالكامل | `AlertOnServiceDown` | إرسال إشعار للفريق الفني |
| `HealthServiceDegraded` | خدمة بأداء متدهور | `TrackServiceDegradation` | تتبع عدد مرات التدهور |
| `HealthServiceRecovered` | عودة خدمة للعمل | `ResetDegradationCounter` | إعادة تعيين عداد التدهور |
