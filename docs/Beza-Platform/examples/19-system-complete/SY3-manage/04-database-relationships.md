# 04 - علاقات قاعدة البيانات: لا توجد جداول (يستخدم أوامر Artisan) (Database Relationships: No Tables, Uses Artisan Commands)

<div dir="rtl">

## نظرة عامة

عملية SY3-manage لا تستخدم أي جداول في قاعدة البيانات بشكل مباشر. جميع العمليات تتم عبر:
1. **أوامر Artisan** (Laravel CLI) لمسح الكاش، إدارة الصيانة، إعادة تشغيل قائمة الانتظار
2. **أوامر shell/system** (exec, shell_exec) للنسخ الاحتياطي باستخدام mysqldump
3. **نظام الملفات** (File System) لقراءة وكتابة السجلات والنسخ الاحتياطية

## لماذا لا توجد جداول؟

| السبب | الشرح |
|-------|--------|
| عمليات النظام لا تتطلب تخزين دائم | مسح الكاش هو عملية مؤقتة، لا حاجة لتخزينها |
| النسخ الاحتياطي يخزن كملفات | أفضل لتجنب تضخم قاعدة البيانات بالبيانات الثنائية |
| السجلات هي ملفات نصية | Laravel يسجل في ملفات، وليس في قاعدة البيانات |
| حالة قائمة الانتظار مؤقتة | تعتمد على Redis/DB Queue Worker، لا حاجة لجدول إضافي |

## ما يمكن اعتباره "علاقة غير مباشرة"

على الرغم من عدم وجود جداول خاصة بـ SY3-manage، إلا أن العملية تتفاعل مع البيانات التالية:

### 1- جدول المستخدمين (users)
```
users
├── id (bigint, PK)
├── name (varchar)
├── email (varchar)
├── password (varchar)
├── role (enum: 'admin', 'manager', 'user')
└── ...
```
**العلاقة**: التحقق من صلاحية المشرف (`role = 'admin'`).

### 2- جدول الوظائف (jobs) — إذا كان Queue Driver = Database
```
jobs
├── id (bigint, PK)
├── queue (varchar)
├── payload (text)
├── attempts (tinyint)
├── reserved_at (timestamp)
├── available_at (timestamp)
└── created_at (timestamp)
```
**العلاقة**: `QueueManager::status()` يقرأ من هذا الجدول لمعرفة عدد الوظائف المعلقة.

### 3- جدول الوظائف الفاشلة (failed_jobs)
```
failed_jobs
├── id (bigint, PK)
├── connection (text)
├── queue (text)
├── payload (text)
├── exception (text)
├── failed_at (timestamp)
└── ...
```
**العلاقة**: يعرض معلومات عن الوظائف الفاشلة إذا طلب QueueManager ذلك.

### 4- الكاش المخزن (Cache)
```
cache  ← إذا كان Cache Driver = Database
├── key (varchar, PK)
├── value (text)
├── expiration (int)
└── ...
```
**العلاقة**: `CacheManager::clear()` يقوم بمسح هذا الجدول (عبر Artisan).

## مخطط العلاقات (للتوضيح فقط)

```
┌──────────────┐       ┌──────────────────┐
│    users     │       │  SystemManage    │
│──────────────│       │  Controller      │
│ id           │───────│                  │
│ email        │  role │  (يستخدم API     │
│ password     │  check│   فقط، لا جداول) │
│ role: admin  │       │                  │
└──────────────┘       └────────┬─────────┘
                                │
                ┌───────────────┼───────────────┐
                │               │               │
                ▼               ▼               ▼
        ┌──────────────┐ ┌──────────┐ ┌──────────────┐
        │    jobs      │ │ failed_  │ │    cache     │
        │              │ │ jobs     │ │              │
        │ queue:       │ │          │ │ key          │
        │ default      │ │ exception│ │ value        │
        │ payload      │ │          │ │ expiration   │
        └──────────────┘ └──────────┘ └──────────────┘
                │               │               │
                └───────────────┴───────────────┘
                        (إذا كان driver = database)
```

## تخزين بيانات العملية

| نوع البيانات | مكان التخزين | الهدف |
|-------------|-------------|-------|
| النسخ الاحتياطية | `storage/app/backups/*.sql.gz` | توفير مساحة في قاعدة البيانات |
| السجلات | `storage/logs/laravel-*.log` | سجل التطبيق |
| السجلات المحددة | `storage/logs/*.log` | سجلات إضافية (وظائف، استعلامات) |
| رسائل وضع الصيانة | ملفات مؤقتة (framework/down) | إعلام المستخدمين |

## توصيات للتوسع المستقبلي

إذا احتجت لتسجيل تاريخ العمليات أو تتبع الاستخدام، يمكن إضافة جدول بسيط:

```sql
CREATE TABLE system_operations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    operation VARCHAR(100) NOT NULL,        -- مثلاً: cache:clear, backup:create
    status ENUM('success', 'failed') NOT NULL,
    details TEXT NULL,                       -- معلومات إضافية
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

لكن هذا خارج نطاق SY3-manage الحالية.

</div>
