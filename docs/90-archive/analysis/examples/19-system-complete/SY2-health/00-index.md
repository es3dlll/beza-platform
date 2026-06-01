# 00 - فهرس التحقق الصحي (Health Check Operation Index)

**الرمز التشغيلي (Operation Code):** SY2-health  
**المنصة (Platform):** Beza Platform  
**الإصدار (Version):** 1.0.0  
**آخر تحديث (Last Updated):** 2026-05-27  

---

## نظرة عامة (Overview)

عملية SY2-health هي نظام التحقق الصحي المتكامل لمنصة بيزا. تقوم بفحص جميع الخدمات الحيوية للنظام (قاعدة البيانات، الذاكرة المؤقتة، قوائم الانتظار، التخزين، إضافات PHP) وتقديم تقرير شامل عن حالة كل خدمة. صمم هذا النظام ليكون نقطة الدخول الأولى لفرق التشغيل والصيانة لضمان استقرار المنصة.

---

## فهرس الملفات (File Index)

| # | الملف (File) | المحتوى (Content) |
|---|-------------|-------------------|
| 00 | `00-index.md` | فهرس العملية والروابط السريعة |
| 01 | `01-business-idea.md` | فكرة العمل وقيمة التحقق الصحي |
| 02 | `02-architecture.md` | رسم архитектуры والاعتماديات بين المكونات |
| 03 | `03-data-flow-sequence.md` | تسلسل تدفق البيانات لطلب التحقق الصحي |
| 04 | `04-database-relationships.md` | علاقات قاعدة البيانات (لا توجد جداول) |
| 05 | `05-migrations.md` | المايقريشنز (لا يوجد) |
| 06 | `06-eloquent-models.md` | موديلات Eloquent (لا يوجد) |
| 07 | `07-validation-rules.md` | قواعد التحقق من صحة المدخلات |
| 08 | `08-controller-full-code.md` | الكود الكامل للـ HealthController |
| 09 | `09-service-layer-core.md` | طبقة الخدمات الأساسية (HealthService وكل المدققين) |
| 10 | `10-service-layer-aux.md` | طبقة الخدمات المساعدة (Formatter + Cache) |
| 11 | `11-events-and-listeners.md` | الأحداث والمستمعين (HealthCheckCompleted) |
| 12 | `12-notification-system.md` | نظام الإشعارات (بريد إلكتروني / رسالة نصية) |
| 13 | `13-exception-handling.md` | معالجة الاستثناءات والتدهور التدريجي |
| 14 | `14-database-transactions-acid.md` | المعاملات الحمضية (غير مطلوبة) |
| 15 | `15-api-specification.md` | توثيق OpenAPI لجميع نقاط النهاية |
| 16 | `16-flutter-implementation.md` | تطبيق Flutter لعرض المؤشرات الصحية |
| 17 | `17-react-implementation.md` | لوحة تحكم React لعرض التحقق الصحي |
| 18 | `18-testing-complete.md` | اختبارات شاملة لكل مدقق |
| 19 | `19-edge-cases.md` | حالات الحافة (Redis معطل، MySQL بطيء، قرص ممتلئ) |
| 20 | `20-security-audit.md` | تدقيق أمني ومنع تسرب المعلومات |

---

## نقاط النهاية (Endpoints)

| الطريقة (Method) | المسار (Path) | الوصف (Description) | المصادقة (Auth) |
|-----------------|---------------|-------------------|----------------|
| GET | `/system/health` | الحالة الصحية العامة للخدمات | اختياري |
| GET | `/system/health/db` | اختبار اتصال MySQL مع وقت الاستعلام | اختياري |
| GET | `/system/health/redis` | اختبار ping لـ Redis | اختياري |
| GET | `/system/health/cache` | اختبار كتابة وقراءة الذاكرة المؤقتة | اختياري |
| GET | `/system/health/queue` | اختبار اتصال قائمة الانتظار | اختياري |
| GET | `/system/health/requirements` | فحص إضافات PHP والإصدارات | اختياري |
| GET | `/system/health/storage` | فحص قابلية الكتابة للتخزين | اختياري |
| GET | `/admin/system/health` | لوحة تحكم مفصلة للمشرفين | مطلوبة (admin) |

---

## هيكل الاستجابة (Response Structure)

```json
{
    "status": "ok",
    "services": [
        {
            "name": "database",
            "status": "up",
            "latency_ms": 2.34,
            "details": {}
        }
    ],
    "timestamp": "2026-05-27T10:30:00Z",
    "cached": false
}
```

---

## روابط سريعة (Quick Links)

- [العودة إلى فهرس النظام الكامل](../README.md)
- [توثيق API العام](../../api/README.md)
- [دليل التشغيل](../../operations/README.md)
