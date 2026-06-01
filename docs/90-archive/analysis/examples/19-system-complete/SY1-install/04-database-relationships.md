# 04 - علاقات قاعدة البيانات (Database Relationships)

## لا توجد جداول خاصة بالمثبت

عملية التنصيب **لا تحتاج إلى أي جداول خاصة بها**. المثبت يقوم فقط بما يلي:

1. **فحص الإضافات** — PHP extensions (BCMath, Ctype, JSON, Mbstring, إلخ) — لا علاقة بقاعدة البيانات
2. **اختبار الاتصال** — MySQL Connection باستخدام PDO — لا ينشئ جداول
3. **كتابة `.env`** — إعدادات التطبيق في ملف — لا ينشئ جداول
4. **تشغيل Artisan** — `key:generate`, `jwt:secret`, `migrate`, `db:seed` — هذه أوامر CLI
5. **إنشاء المشرف** — يتم إدراج صف واحد في جدول `users` (الموجود بالفعل بعد `migrate`)
6. **تعطيل المثبت** — يكتب `INSTALLER_LOCKED=true` في `.env`

## أين يتم تخزين حالة المثبت؟

```dotenv
# في ملف .env (وليس في قاعدة البيانات)
INSTALLER_LOCKED=true
INSTALLER_COMPLETED_AT=2026-05-27T14:30:00+03:00
INSTALLER_VERSION=1.0.0
```

## لماذا لا نستخدم جدولاً لحالة المثبت؟

| السبب | الشرح |
|-------|-------|
| **المثبت يعمل قبل قاعدة البيانات** | في أول خطوة، قد لا تكون MySQL جاهزة بعد |
| **لا حاجة لاستعلامات** | قراءة متغير من `.env` أسرع من استعلام SQL |
| **يبسط المنطق** | لا حاجة لإنشاء جدول `installations` |
| **الأمان** | لا يمكن العبث بحالة المثبت عبر SQL injection |
| **قابلية النقل** | إذا تم نقل قاعدة البيانات، يظل المثبت معطلاً |

## هيكل users (بعد الترحيل)

```sql
-- جدول users — يُضاف إليه المشرف الأول فقط
-- هذا الجدول يتم إنشاؤه بواسطة migrate --force في الخطوة 4
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    email_verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## ملخص

```
┌──────────────────────────────────────────────────────────┐
│              عملية التنصيب (SY1-install)                   │
│                                                          │
│   لا يوجد ER Diagram — المثبت لا يملك جداول خاصة          │
│                                                          │
│   يعتمد على:                                              │
│   ├── ملف .env (إعدادات + حالة المثبت)                   │
│   ├── PHP extensions (فحص الإضافات)                       │
│   ├── PDO (اختبار MySQL)                                  │
│   ├── Redis (اختبار الاتصال)                              │
│   ├── Artisan CLI (ميجريشن + سيد)                         │
│   └── جدول users (فقط عند إنشاء المشرف)                   │
└──────────────────────────────────────────────────────────┘
```
