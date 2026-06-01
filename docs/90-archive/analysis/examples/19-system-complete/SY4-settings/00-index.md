# 00 - فهرس إعدادات النظام (Settings Index)

## نظرة عامة (Overview)

ملف **SY4-settings** هو المسؤول عن إدارة جميع إعدادات النظام في منصة بيزا. يتم تخزين الإعدادات في جدول `system_settings` مع دعم كامل للتخزين المؤقت في Redis. توفر هذه الوحدة واجهة برمجة تطبيقات REST كاملة تسمح للمسؤولين بعرض وتحديث الإعدادات حسب المجموعات.

## هيكل الملفات (File Structure)

| رقم الملف | اسم الملف | المحتوى |
|-----------|-----------|---------|
| 00 | `00-index.md` | فهرس إعدادات النظام |
| 01 | `01-business-idea.md` | فكرة العمل وقيمة إدارة الإعدادات المركزية |
| 02 | `02-architecture.md` | بنية النظام: Controller → Service → Cache → Database |
| 03 | `03-data-flow-sequence.md` | تدفق البيانات: قراءة وكتابة مع التخزين المؤقت |
| 04 | `04-database-relationships.md` | علاقات قاعدة البيانات (جدول واحد مستقل) |
| 05 | `05-migrations.md` | هيكلة جدول system_settings وإضافة القيم الافتراضية |
| 06 | `06-eloquent-models.md` | موديل SystemSetting |
| 07 | `07-validation-rules.md` | قواعد التحقق لكل مجموعة إعدادات |
| 08 | `08-controller-full-code.md` | كود كامل لـ SystemSettingsController |
| 09 | `09-service-layer-core.md` | SettingsService الأساسي |
| 10 | `10-service-layer-aux.md` | SettingsCacheManager و SettingsHelper و SettingsValidator |
| 11 | `11-events-and-listeners.md` | حدث SettingUpdated ومستمع تحديث التخزين المؤقت |
| 12 | `12-notification-system.md` | نظام الإشعارات (لا توجد إشعارات) |
| 13 | `13-exception-handling.md` | معالجة الاستثناءات |
| 14 | `14-database-transactions-acid.md` | المعاملات الذرية لتحديث الإعدادات |
| 15 | `15-api-specification.md` | توثيق OpenAPI لجميع نقاط النهاية |
| 16 | `16-flutter-implementation.md` | تنفيذ Flutter لقراءة الإعدادات |
| 17 | `17-react-implementation.md` | صفحة إعدادات React مع تبويبات المجموعات |
| 18 | `18-testing-complete.md` | اختبار شامل لجميع الوظائف |
| 19 | `19-edge-cases.md` | الحالات الحدية: التحديث المتزامن، انهيار التخزين المؤقت |
| 20 | `20-security-audit.md` | تدقيق أمني: صلاحيات المسؤول، التحقق من المدخلات |

## نقاط النهاية API (API Endpoints)

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| GET | `/admin/system/settings` | عرض جميع الإعدادات مجمعة حسب الفئة |
| PUT | `/admin/system/settings/general` | تحديث الإعدادات العامة (اسم التطبيق، الوصف، الشعار) |
| PUT | `/admin/system/settings/features` | تحديث خصائص المنصة (تفعيل/تعطيل الميزات) |
| PUT | `/admin/system/settings/fees` | تحديث نسب الرسوم الافتراضية |
| PUT | `/admin/system/settings/limits` | تحديث الحدود (التحويل اليومي، الرصيد الأقصى) |
| PUT | `/admin/system/settings/exchange` | تحديث إعدادات صرف العملات |
| PUT | `/admin/system/settings/security` | تحديث إعدادات الأمان (محاولات تسجيل الدخول) |
| PUT | `/admin/system/settings/notifications` | تحديث قنوات الإشعارات الافتراضية |
| PUT | `/admin/system/settings/mail` | تحديث إعدادات البريد الإلكتروني SMTP |
| PUT | `/admin/system/settings/maintenance` | تحديث وضع الصيانة |

## الإعدادات الافتراضية (Default Settings)

```php
// الإعدادات العامة
'general.app_name'        => 'Beza',
'general.timezone'        => 'Asia/Riyadh',
'general.locale'          => 'ar',

// نسب الرسوم
'fees.p2p'                => 0,
'fees.exchange'           => 1.5,
'fees.card_deposit'       => 2.5,

// الحدود
'limits.daily_transfer'   => 100000,
'limits.max_wallet'       => 500000,
'limits.min_withdrawal'   => 100,

// الأمان
'security.max_attempts'   => 5,
'security.lockout_minutes'=> 30,
```

## تقنيات مستخدمة (Technologies Used)

- **Laravel Framework** — إطار العمل الرئيسي
- **Redis Cache** — تخزين مؤقت للإعدادات مع TTL ساعة واحدة
- **JWT (auth:api)** — مصادقة المسؤول باستخدام JSON Web Tokens
- **MySQL** — قاعدة بيانات لتخزين الإعدادات
