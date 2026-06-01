# AD7 — Agent Oversight APIs | واجهات رقابة المشرف على الوكلاء

**التاريخ:** 2026-06-01  
**الحالة:** ✅ مكتمل  
**الوحدة:** Admin (Modules/Admin)  
**الأولوية:** عالية  

---

## الملخص

بناء واجهات REST API إدارية للإشراف على الوكلاء في منصة Beza. تتيح هذه الواجهات للمشرفين إدارة الوكلاء، مراجعة العمولات، الموافقة على التسويات، ومراقبة التنبيهات الأمنية عبر مسار `/api/v1/admin/`.

---

## نقاط الـ API المنفذة

| المسار | الطريقة | الوظيفة |
|--------|---------|---------|
| `/agents` | GET | قائمة الوكلاء مع فلترة |
| `/agents/{id}` | GET | تفاصيل وكيل كامل |
| `/agents/{id}/commissions` | GET | سجل عمولات وكيل |
| `/commissions/{id}/approve` | POST | اعتماد عمولة + قيد في دفتر الأستاذ |
| `/agents/{id}/settlements` | GET | سجل تسويات وكيل |
| `/settlements/{id}/approve` | POST | اعتماد تسوية + قيد في دفتر الأستاذ |
| `/fraud-alerts` | GET | قائمة التنبيهات الأمنية |
| `/fraud-alerts/{id}/resolve` | POST | حل تنبيه أمني |

---

## الملفات المنشأة

### جديد
- `app/Modules/Admin/Controllers/AgentOversightController.php`
- `app/Modules/Admin/Services/AgentAdminService.php`
- `app/Modules/Agent/Models/FraudAlert.php`
- `app/Modules/Agent/Models/LedgerEntry.php`
- `database/migrations/2026_06_01_040004_create_fraud_alerts_table.php`
- `database/migrations/2026_06_01_040005_create_ledger_entries_table.php`
- `tests/Feature/Admin/AgentOversightTest.php`
- `docs/05-api/admin-agent-oversight-v1.yaml`

### معدّل
- `app/Modules/Admin/routes/api.php` — إضافة المسارات الجديدة، تحديث البادئة إلى `v1/admin`
- `app/Models/User.php` — إضافة الصلاحيات الجديدة
- `bootstrap/app.php` — إضافة `admin_token` إلى قائمة استثناء تشفير الكعكات
- `app/Http/Middleware/AdminAuth.php` — دعم Bearer token كخيار بديل

---

## الامتثال المالي (WORM)

جميع عمليات الاعتماد المالي (العمولات والتسويات) تُسجل كقيد جديد في جدول `ledger_entries`:
- **ممنوع UPDATE/DELETE** على السجلات المالية
- استخدام `DB::transaction` مع `lockForUpdate()` لمنع سباقات التوقيت
- `LedgerEntry` تسجل: الرصيد قبل وبعد، الجهة المصادقة، الاتجاه (credit)

---

## الاختبارات

- **16 اختبار** (Feature) يغطي جميع نقاط الـ API
- النتائج: 200 (نجاح), 401 (دون مصادقة), 403 (بدون صلاحية), 404 (غير موجود), 422 (خطأ منطقي)
- جميع الاختبارات تمر: ✅ 16/16

---

## الإذونات المطلوبة

| الصلاحية | الوظيفة |
|----------|---------|
| `agents:view` | عرض قائمة وتفاصيل الوكلاء |
| `agents:commissions` | عرض سجل العمولات |
| `commissions:approve` | اعتماد العمولات |
| `agents:finance` | عرض سجل التسويات |
| `finance:approve` | اعتماد التسويات |
| `security:view` | عرض التنبيهات الأمنية |
| `security:resolve` | حل التنبيهات الأمنية |
