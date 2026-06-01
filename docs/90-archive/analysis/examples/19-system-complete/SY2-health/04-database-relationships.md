# 04 - علاقات قاعدة البيانات (Database Relationships)

**الرمز التشغيلي:** SY2-health  
**النوع:** توثيق قاعدة البيانات (Database Documentation)

---

## خلاصة (Summary)

عملية SY2-health **لا تتطلب أي جداول** في قاعدة البيانات. هذا تصميم متعمد لأن نظام التحقق الصحي يجب أن يعمل حتى في حال تعطل قاعدة البيانات نفسها.

---

## لماذا لا نحتاج جداول؟ (Why No Tables Needed?)

| السبب (Reason) | الشرح (Explanation) |
|---------------|-------------------|
| استقلالية الفحص | لو اعتمدنا على قاعدة البيانات لتخزين النتائج، فشل DB يعني فشل الفحص |
| سرعة الأداء | كتابة النتائج في DB كل 30 ثانية تستهلك موارد إضافية غير ضرورية |
| بساطة التصميم | التخزين المؤقت في Redis يكفي لمنع DoS |
| تكامل مع أدوات خارجية | نتائج التحقق تستخدم مباشرة (JSON) بدون وسيط DB |

---

## أين تخزن النتائج؟ (Where Are Results Stored?)

بدلاً من قاعدة البيانات، تستخدم النتائج:

1. **التخزين المؤقت (Cache):** نتائج `GET /system/health` تخزن في Redis/Cache لمدة 30 ثانية.
2. **ذاكرة الطلب (Request Memory):** لكل طلب يتم تجميع النتائج في مصفوفة PHP عادية.
3. **السجلات (Logs):** عند اكتشاف خدمة معطلة، يتم تسجيل الحدث في ملف السجل (`storage/logs/health-YYYY-MM-DD.log`).

```
نظام التخزين:
┌──────────────┐   30 ثانية    ┌──────────────────┐
│ Cache (Redis)│◄─────────────│ نتائج التحقق الصحي│
│ key: health:*│              └──────────────────┘
└──────────────┘
                                        │
                                        ▼
                               ┌──────────────────┐
                               │  ملفات السجل     │
                               │  storage/logs/   │
                               └──────────────────┘
```

---

## هل هناك أي جداول مرتبطة؟ (Any Related Tables?)

بشكل غير مباشر، قد ترتبط عملية SY2-health بالجداول التالية من عمليات أخرى (اختياري):

| الجدول (Table) | العملية (Operation) | العلاقة (Relationship) |
|---------------|---------------------|----------------------|
| `users` | SY1-auth | عند الوصول إلى `/admin/system/health`، نتحقق من دور المستخدم |
| `roles` | SY1-auth | للتأكد من أن المستخدم يملك صلاحية admin |
| `failed_jobs` | SY3-queue | إذا فشلت مهمة بسبب Redis معطل، يظهر في التحقق |

---

## بديل تصميم تخزين النتائج (Alternative Design — Not Implemented)

لو احتجنا في المستقبل تتبع تاريخي للتحقق الصحي، يمكننا إضافة جدول:

```sql
CREATE TABLE health_check_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(50) NOT NULL,       -- database, redis, cache, queue
    status ENUM('up', 'down', 'degraded') NOT NULL,
    latency_ms DECIMAL(8, 2) DEFAULT 0.00,
    error_message TEXT NULL,
    checked_by VARCHAR(255) NULL,            -- المستخدم أو النظام الآلي
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_health_service (service_name),
    INDEX idx_health_status (status),
    INDEX idx_health_created (created_at)
);
```

**لماذا لم نطبق هذا حالياً:**
- يزيد من تعقيد النظام
- يستهلك مساحة تخزين كبيرة مع مرور الوقت
- يمكن استبداله بأدوات خارجية مثل ELK أو Grafana

---

## الخلاصة (Conclusion)

لا توجد علاقات قاعدة بيانات لـ SY2-health. النظام مصمم ليكون مستقلاً تماماً عن قاعدة البيانات لضمان فحص دقيق حتى في أسوأ السيناريوهات. التخزين المؤقت فقط في Redis/cache مع مدة صلاحية 30 ثانية.
