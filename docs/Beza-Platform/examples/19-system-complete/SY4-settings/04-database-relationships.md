# 04 - علاقات قاعدة البيانات: جدول مستقل لا يرتبط بجداول أخرى (Database Relationships)

## نظرة عامة (Overview)

جدول `system_settings` هو جدول مستقل **لا يرتبط بأي جدول آخر** في قاعدة البيانات. هذا التصميم مقصود لأن إعدادات النظام هي كيانات قائمة بذاتها تؤثر على المنصة بأكملها ولا تنتمي إلى كيان معين.

```php
// // system_settings هو جدول وحيد لا توجد له علاقات مع جداول أخرى
// // لا توجد مفاتيح خارجية (Foreign Keys)
// // لا توجد علاقات one-to-many أو many-to-many
// // هذا التبسيط مقصود للسرعة والمرونة
```

## هيكل الجدول (Table Structure)

```sql
CREATE TABLE `system_settings` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group`       VARCHAR(50)     NOT NULL COMMENT 'مجموعة الإعداد: general, features, fees, limits, exchange, security, notifications, mail, maintenance',
    `key`         VARCHAR(100)    NOT NULL COMMENT 'مفتاح الإعداد الفريد ضمن المجموعة',
    `value`       TEXT            NULL     COMMENT 'قيمة الإعداد (نص، رقم، JSON، منطقي)',
    `type`        VARCHAR(20)     NOT NULL DEFAULT 'string' COMMENT 'نوع القيمة: string, integer, float, boolean, json',
    `description` TEXT            NULL     COMMENT 'وصف الإعداد بالعربية والإنجليزية',
    `created_at`  TIMESTAMP       NULL     DEFAULT NULL,
    `updated_at`  TIMESTAMP       NULL     DEFAULT NULL,

    UNIQUE INDEX `uq_system_settings_group_key` (`group`, `key`),
    INDEX `idx_system_settings_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## لماذا لا توجد علاقات؟ (Why No Relationships?)

```php
// // 1. الإعدادات ليست تابعة لكيان معين
// //    - general.app_name هو اسم المنصة، لا ينتمي لمستخدم أو شركة
// //    - fees.p2p هي نسبة رسوم شاملة، لا ترتبط بمعاملة محددة

// // 2. الإعدادات عالمية المستوى (Global)
// //    - جميع الإعدادات تؤثر على كل المستخدمين
// //    - لا توجد إعدادات خاصة بمستخدم أو دور معين في هذا الجدول

// // 3. الأداء
// //    - عدم وجود JOINs يسرع القراءة والكتابة
// //    - التخزين المؤقت في Redis يقلل ضغط قاعدة البيانات

// // 4. البساطة
// //    - هيكل key-value سهل الفهم والصيانة
// //    - إضافة إعدادات جديدة لا يتطلب تغيير هيكل الجدول
```

## أنواع القيم المدعومة (Supported Value Types)

```php
// // يحدد حقل type كيفية تفسير القيمة المخزنة:

$typeMap = [
    'string'  => 'قيمة نصية عادية',        // general.app_name = "Beza"
    'integer' => 'رقم صحيح',              // security.max_attempts = 5
    'float'   => 'رقم عشري',              // fees.exchange = 1.5
    'boolean' => 'قيمة منطقية (true/false)', // features.gold = true
    'json'    => 'مصفوفة أو كائن JSON',    // maintenance.allowed_ips = ["192.168.1.1"]
];

// // أمثلة على القيم المخزنة:

// general.app_name: { "value": "Beza", "type": "string" }
// features.gold:    { "value": "1",    "type": "boolean" }
// fees.exchange:    { "value": "1.5",  "type": "float" }
// limits.max_wallet:{ "value": "500000","type": "integer" }
// mail.smtp:        { "value": "{\"host\":\"...\"}", "type": "json" }
```

## دالة تحويل النوع (Type Casting)

```php
// // موديل SystemSetting يحول القيمة تلقائياً حسب النوع
// // هذا يضمن أن التطبيق يتعامل مع الأنواع الصحيحة

class SystemSetting extends Model
{
    // // تحويل القيمة من نص إلى النوع المناسب
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => (bool) $this->value,
            'integer' => (int) $this->value,
            'float'   => (float) $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value, // string
        };
    }

    // // تحويل القيمة إلى نص للتخزين
    public function setTypedValue(mixed $value): void
    {
        $this->value = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'json'    => json_encode($value),
            default   => (string) $value,
        };
    }
}
```

## إحصائيات الجدول (Table Statistics)

```php
// // عدد الإعدادات الافتراضية عند التثبيت الأول: 35 إعداد
// // مقسمة على 9 مجموعات
$defaultSettingsCount = [
    'general'       => 6,  // اسم، وصف، شعار، فافيكون، توقيت، لغة
    'features'      => 5,  // gold, deals, cards, agents, loans
    'fees'          => 4,  // p2p, exchange, card_deposit, withdrawal
    'limits'        => 3,  // daily_transfer, max_wallet, min_withdrawal
    'exchange'      => 2,  // margin, update_interval
    'security'      => 3,  // max_attempts, lockout_minutes, password_policy
    'notifications' => 1,  // default_channels
    'mail'          => 1,  // smtp (JSON)
    'maintenance'   => 2,  // mode, message, allowed_ips
];
```

## عدم وجود علاقات (No Relationships Summary)

```php
// // الخلاصة: جدول system_settings هو جدول ORPHAN (يتيم)
// // - لا parent relationship
// // - لا child relationships
// // - لا polymorphic relationships
// // - لا pivot tables
// // - لا foreign keys
// // 
// // هذا التصميم البسيط هو مفتاح السرعة والمرونة
// // إضافة إعداد جديد = إدراج سطر جديد في الجدول
// // تغيير إعداد = تحديث value + type
```
