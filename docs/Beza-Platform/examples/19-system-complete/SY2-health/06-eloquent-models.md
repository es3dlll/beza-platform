# 06 - موديلات Eloquent (Eloquent Models)

**الرمز التشغيلي:** SY2-health  
**النوع:** توثيق الموديلات (Model Documentation)

---

## خلاصة (Summary)

عملية SY2-health **لا تتطلب أي موديلات Eloquent**. لا توجد جداول في قاعدة البيانات، وبالتالي لا نحتاج موديلات.

---

## لماذا لا يوجد موديلات؟ (Why No Models?)

```php
<?php

namespace App\Models;

// لا يوجد موديل HealthCheckLog
// لا يوجد موديل ServiceStatus
// لا يوجد موديل SystemHealth
// كل هذه غير موجودة عمداً!

/*
 * الأسباب الهندسية:
 *
 * 1. Eloquent models ترتبط بجداول في قاعدة البيانات
 *    - التحقق الصحي لا يستخدم قواعد بيانات
 *    - استخدام Eloquent سيكون مضللاً
 *
 * 2. النتائج مؤقتة (غير مخزنة في DB)
 *    - لا فائدة من موديل لعرض بيانات وقتية
 *    - البيانات تتغير كل 30 ثانية
 *
 * 3. المدققين يستخدمون Facades مباشرة
 *    - DB::select() لفحص MySQL
 *    - Redis::connection() لفحص Redis
 *    - Cache::put() / Cache::get() للكاش
 *    - هذه لا تحتاج موديلات
 */
```

---

## البديل: Value Objects (كائنات قيمة)

بدلاً من Eloquent Models، نستخدم Value Objects بسيطة لتغليف نتائج الفحص:

```php
<?php

namespace App\Services\Health\ValueObjects;

/**
 * كائن قيمة يمثل نتيجة فحص خدمة واحدة
 * لا يرث من Model لأنه لا يخزن في قاعدة البيانات
 */
class HealthCheckResult
{
    /**
     * @param string $name اسم الخدمة
     * @param string $status الحالة: up, down, degraded
     * @param float $latency_ms زمن الاستجابة بالمللي ثانية
     * @param array $details تفاصيل إضافية
     * @param string|null $error رسالة الخطأ إن وجد
     */
    public function __construct(
        public readonly string  $name,
        public readonly string  $status,
        public readonly float   $latency_ms = 0.0,
        public readonly array   $details = [],
        public readonly ?string $error = null,
    ) {}

    /**
     * تحويل الكائن إلى مصفوفة للإرجاع كـ JSON
     *
     * @return array
     */
    public function toArray(): array
    {
        // ترجمة: تجهيز البيانات للإرسال
        $result = [
            'name'       => $this->name,
            'status'     => $this->status,
            'latency_ms' => $this->latency_ms,
        ];

        // ترجمة: إضافة التفاصيل إذا وجدت
        if (!empty($this->details)) {
            $result['details'] = $this->details;
        }

        // ترجمة: إضافة الخطأ إذا وجدت الخدمة معطلة
        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
    }

    /**
     * هل الخدمة تعمل بشكل طبيعي؟
     *
     * @return bool
     */
    public function isUp(): bool
    {
        return $this->status === 'up';
    }

    /**
     * هل الخدمة بحالة متدهورة؟
     *
     * @return bool
     */
    public function isDegraded(): bool
    {
        return $this->status === 'degraded';
    }

    /**
     * هل الخدمة معطلة؟
     *
     * @return bool
     */
    public function isDown(): bool
    {
        return $this->status === 'down';
    }

    /**
     * إنشاء نتيجة ناجحة
     *
     * @param string $name
     * @param float $latency_ms
     * @param array $details
     * @return self
     */
    public static function up(string $name, float $latency_ms = 0.0, array $details = []): self
    {
        // ترجمة: إنشاء نتيجة نجاح
        return new self($name, 'up', $latency_ms, $details);
    }

    /**
     * إنشاء نتيجة فشل
     *
     * @param string $name
     * @param string $error
     * @return self
     */
    public static function down(string $name, string $error = ''): self
    {
        // ترجمة: إنشاء نتيجة فشل
        return new self($name, 'down', 0.0, [], $error);
    }

    /**
     * إنشاء نتيجة متدهورة
     *
     * @param string $name
     * @param float $latency_ms
     * @param string $warning
     * @return self
     */
    public static function degraded(string $name, float $latency_ms = 0.0, string $warning = ''): self
    {
        // ترجمة: إنشاء نتيجة تحذيرية
        return new self($name, 'degraded', $latency_ms, ['warning' => $warning]);
    }
}
```

```php
<?php

namespace App\Services\Health\ValueObjects;

/**
 * كائن قيمة يمثل التقرير الصحي الكامل
 */
class HealthReport
{
    /**
     * @param string $status الحالة العامة
     * @param array $results مصفوفة نتائج الخدمات
     * @param bool $cached هل هذه نتائج مخزنة مؤقتاً
     */
    public function __construct(
        public readonly string $status,
        public readonly array  $results = [],
        public readonly bool   $cached = false,
    ) {}

    /**
     * تحويل التقرير إلى مصفوفة
     *
     * @return array
     */
    public function toArray(): array
    {
        // ترجمة: تحويل النتائج إلى مصفوفات
        $services = array_map(
            fn(HealthCheckResult $r) => $r->toArray(),
            $this->results
        );

        return [
            'status'    => $this->status,
            'services'  => $services,
            'timestamp' => now()->toIso8601String(),
            'cached'    => $this->cached,
        ];
    }
}
```

---

## ملخص (Summary)

| الموديل (Model) | هل يوجد؟ (Exists?) | السبب (Reason) |
|----------------|-------------------|---------------|
| `HealthCheckResult` | لا (Value Object) | ليس Eloquent model، مجرد value object |
| `HealthReport` | لا (Value Object) | لتجميع النتائج مؤقتاً في الذاكرة |
| أي موديل Eloquent | لا | لا توجد جداول مرتبطة |

نستخدم **Value Objects** بدلاً من **Eloquent Models** لأن البيانات مؤقتة ولا تحتاج تخزين في قاعدة البيانات.
