# 05 - المايقريشنز (Migrations)

**الرمز التشغيلي:** SY2-health  
**النوع:** توثيق الهجرة (Migration Documentation)

---

## خلاصة (Summary)

عملية SY2-health **لا تتطلب أي مايقرشنز** (مigrations). لا توجد جداول جديدة يتم إنشاؤها في قاعدة البيانات.

---

## لماذا لا يوجد مايقرشنز؟ (Why No Migrations?)

```php
<?php
// الملف: database/migrations/XXXX_XX_XX_XXXXXX_create_health_tables.php
// لا يوجد! (Does not exist!)

/*
 * شرح القرار الهندسي:
 *
 * 1. عملية التحقق الصحي يجب أن تعمل بدون اتصال بقاعدة البيانات
 *    - لو أضفنا جداول، فشل DB يعني فشل إنشاء الجداول
 *    - هذا يتعارض مع هدف العملية الأساسي
 *
 * 2. كل النتائج تخزن مؤقتاً في Redis
 *    - مايقرشنز Redis تكون عبر التهيئة فقط (config/database.php)
 *    - لا نحتاج schema لقاعدة بيانات غير علائقية
 *
 * 3. السجلات تكتب في ملفات (storage/logs/)
 *    - لا تحتاج جداول
 *    - استخدام Monolog مع channel مخصص
 *
 * 4. البساطة هي الهدف
 *    - بدون DB = بدون failpoint إضافي
 *    - بدون migration = بدون تعقيد في النشر
 */
```

---

## التهيئة المطلوبة بدلاً من المايقريشنز (Configuration Instead of Migrations)

بدلاً من إنشاء جداول، نحتاج فقط تعديل ملفات التهيئة:

### 1. ملف `config/health.php` (جديد)

```php
<?php
// config/health.php - إعدادات نظام التحقق الصحي

return [
    /*
     * المدة الزمنية للتخزين المؤقت بالثواني
     * يمنع هجمات DoS على نقاط الفحص
     */
    'cache_ttl' => env('HEALTH_CACHE_TTL', 30),

    /*
     * المهلة الزمنية لكل فحص (بالثواني)
     * يمنع تعليق الطلب إذا كانت خدمة بطيئة
     */
    'timeout' => env('HEALTH_CHECK_TIMEOUT', 5),

    /*
     * الخدمات التي سيتم فحصها
     * يمكن تعطيل فحص خدمة معينة من هنا
     */
    'services' => [
        'database' => env('HEALTH_CHECK_DB', true),
        'redis'    => env('HEALTH_CHECK_REDIS', true),
        'cache'    => env('HEALTH_CHECK_CACHE', true),
        'queue'    => env('HEALTH_CHECK_QUEUE', true),
        'storage'  => env('HEALTH_CHECK_STORAGE', true),
        'php'      => env('HEALTH_CHECK_PHP', true),
    ],

    /*
     * إضافات PHP المطلوبة
     * يتم فحصها في /system/health/requirements
     */
    'required_extensions' => [
        'pdo',
        'mbstring',
        'json',
        'openssl',
        'tokenizer',
        'ctype',
        'redis',
        'bcmath',
        'xml',
        'fileinfo',
        'gd',
    ],

    /*
     * إصدار PHP الأدنى المطلوب
     */
    'min_php_version' => '8.1.0',

    /*
     * المجلدات المراد فحص صلاحيات الكتابة لها
     */
    'writable_directories' => [
        storage_path('logs'),
        storage_path('framework/cache/data'),
        storage_path('framework/sessions'),
        storage_path('framework/views'),
        public_path('uploads'),
    ],

    /*
     * حد الإنذار للمساحة التخزينية (بالنسبة المئوية)
     * عندما يزيد الاستهلاك عن 90%، يتم إرجاع تحذير
     */
    'disk_warning_percent' => 90,
];
```

### 2. ملف `config/logging.php` (تعديل)

```php
<?php
// إضافة channel مخصص لسجلات التحقق الصحي

'health' => [
    'driver' => 'daily',
    'path' => storage_path('logs/health.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 30,
],
```

### 3. متغيرات البيئة (`.env`)

```
# إعدادات التحقق الصحي
HEALTH_CACHE_TTL=30
HEALTH_CHECK_TIMEOUT=5
HEALTH_CHECK_DB=true
HEALTH_CHECK_REDIS=true
HEALTH_CHECK_CACHE=true
HEALTH_CHECK_QUEUE=true
HEALTH_CHECK_STORAGE=true
HEALTH_CHECK_PHP=true
```

---

## ماذا لو احتجنا مايقرشنز في المستقبل؟ (Future Migrations)

إذا أردنا إضافة تتبع تاريخي، سنحتاج:

```bash
# إنشاء مايقرشن مستقبلي
php artisan make:migration create_health_check_logs_table
```

لكن هذا غير مطلوب حالياً. النظام يعمل بالكامل بدون أي مايقرشنز.
