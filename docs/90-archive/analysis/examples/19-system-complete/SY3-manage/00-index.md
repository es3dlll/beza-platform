# 00 - فهرس إدارة النظام (System Management Index)

<div dir="rtl">

## نظرة عامة

توثيق عملية **SY3-manage** لإدارة النظام في منصة بزة. توفر هذه العملية واجهة برمجية (API) وسطر أوامر (CLI) للمشرفين للتحكم في جميع جوانب إدارة النظام مثل مسح الذاكرة المؤقتة، إدارة السجلات، مراقبة قوائم الانتظار، النسخ الاحتياطي، والصيانة.

## قائمة الملفات

| # | الملف | الوصف |
|---|-------|--------|
| 00 | `00-index.md` | فهرس إدارة النظام |
| 01 | `01-business-idea.md` | فكرة العمل: لماذا يحتاج مدير النظام أدوات تشبه CLI عبر واجهة الويب |
| 02 | `02-architecture.md` | الرسم المعماري: SystemManageController ← CacheManager, LogManager, QueueManager, MaintenanceManager, BackupManager |
| 03 | `03-data-flow-sequence.md` | تدفق البيانات: مسح الكاش، إنشاء نسخة احتياطية، تبديل وضع الصيانة |
| 04 | `04-database-relationships.md` | علاقات قاعدة البيانات: لا توجد جداول (يستخدم أوامر Artisan) |
| 05 | `05-migrations.md` | الترحيلات: لا توجد ترحيلات لقاعدة البيانات |
| 06 | `06-eloquent-models.md` | نماذج Eloquent: لا توجد نماذج |
| 07 | `07-validation-rules.md` | قواعد التحقق: صلاحية المشرف، التحقق من معرف النسخة الاحتياطية |
| 08 | `08-controller-full-code.md` | كود المتحكم الكامل: SystemManageController مع جميع النقاط النهائية |
| 09 | `09-service-layer-core.md` | طبقة الخدمات الأساسية: CacheManager, LogManager, QueueManager, MaintenanceManager |
| 10 | `10-service-layer-aux.md` | طبقة الخدمات المساعدة: BackupManager (غلاف mysqldump), SystemInfoCollector |
| 11 | `11-events-and-listeners.md` | الأحداث والمستمعون: CacheCleared, BackupCreated, MaintenanceModeChanged |
| 12 | `12-notification-system.md` | نظام الإشعارات: إخطار المشرف عند اكتمال/فشل النسخة الاحتياطية |
| 13 | `13-exception-handling.md` | معالجة الاستثناءات: فشل أوامر Artisan، امتلاء القرص أثناء النسخ الاحتياطي |
| 14 | `14-database-transactions-acid.md` | معاملات قاعدة البيانات: لا توجد معاملات، لكن النسخ الاحتياطي يستخدم --single-transaction |
| 15 | `15-api-specification.md` | مواصفات API: OpenAPI لجميع نقاط الإدارة |
| 16 | `16-flutter-implementation.md` | تطبيق Flutter: شاشة إدارة النظام للمشرف |
| 17 | `17-react-implementation.md` | تطبيق React: صفحة إدارة النظام مع تبويبات |
| 18 | `18-testing-complete.md` | الاختبارات: اختبار مع Artisan وهمي و mysqldump وهمي |
| 19 | `19-edge-cases.md` | حالات الحافة: امتلاء القرص، رفض الإذن، تعطيل exec في PHP |
| 20 | `20-security-audit.md` | تدقيق أمني: وصول المشرف فقط، لا حقن أوامر، التحقق من جميع أوامر exec |

## نقاط النهاية API

| الطريقة | المسار | الوصف |
|--------|-------|-------|
| POST | `/admin/system/cache/clear` | مسح جميع أنواع الذاكرة المؤقتة |
| POST | `/admin/system/cache/optimize` | تحسين الكاش وتخزين المسارات |
| POST | `/admin/system/log/view` | عرض أحدث إدخالات السجل |
| POST | `/admin/system/log/clear` | مسح ملفات السجل |
| GET | `/admin/system/queue/status` | حالة عمال قائمة الانتظار |
| POST | `/admin/system/queue/restart` | إعادة تشغيل عمال قائمة الانتظار |
| GET | `/admin/system/schedule` | عرض المهام المجدولة |
| POST | `/admin/system/maintenance` | تفعيل/تعطيل وضع الصيانة |
| GET | `/admin/system/info` | معلومات النظام: PHP, Laravel, البيئة |
| POST | `/admin/system/backup` | إنشاء نسخة احتياطية لقاعدة البيانات |
| GET | `/admin/system/backup/list` | عرض النسخ الاحتياطية المتاحة |
| POST | `/admin/system/backup/{id}/restore` | استعادة نسخة احتياطية |
| DELETE | `/admin/system/backup/{id}` | حذف ملف النسخة الاحتياطية |
| GET | `/admin/system/logs` | عرض ملفات السجل مع الأحجام |
| GET | `/admin/system/logs/{file}` | عرض ملف سجل محدد |

## المصادقة

جميع نقاط النهاية محمية بواسطة `auth:api` (JWT) وتتطلب دور `admin`.

## الأمان

- التحقق من الدور: `admin` فقط
- لا توجد حقن أوامر: التحقق من جميع المدخلات قبل تمريرها إلى exec/shell
- صلاحيات الملفات: التحقق من وجود الملفات وإمكانية الوصول إليها قبل القراءة أو الحذف
- وضع الصيانة: يتطلب تأكيد الإجراء

</div>
