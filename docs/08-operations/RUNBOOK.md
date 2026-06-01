# دليل التشغيل التشغيلي — منصة بيزا

**الإصدار:** v1.1.0-beta
**آخر تحديث:** 2026-06-01
**الجمهور المستهدف:** فريق الدعم، مشغلو النظام، المطورون المناوبون

---

## جدول المحتويات

1. [نظرة عامة على النظام](#1-نظرة-عامة)
2. [المراقبة والتنبيهات](#2-المراقبة)
3. [النسخ الاحتياطي والاستعادة](#3-النسخ-الاحتياطي)
4. [إعادة تشغيل الخدمات](#4-إعادة-التشغيل)
5. [إجراءات الطوارئ](#5-الطوارئ)
6. [الفحص الصحي](#6-الفحص-الصحي)
7. [إدارة البيئات](#7-البيئات)
8. [جهات الاتصال](#8-جهات-الاتصال)

---

## 1. نظرة عامة على النظام

### المكوّنات الرئيسية
| المكوّن | التقنية | المنفذ الافتراضي |
|---------|---------|-----------------|
| Backend API | Laravel 13 | 8000 |
| Queue Worker | Laravel Queue (Database) | — |
| Admin Panel | React 19 + Vite | 5173 |
| Mobile App | Flutter 3.41 | — |
| Database | SQLite (dev) / PostgreSQL (prod) | — |

### بنية المجلدات
```
Beza-Platform/
├── backend/           # Laravel API — 14 وحدة
├── frontend/
│   ├── admin/         # لوحة الإدارة React
│   └── mobile/        # تطبيق الموبايل Flutter
└── docs/              # التوثيق
```

---

## 2. المراقبة والتنبيهات

### نقطة الفحص الصحي
```
GET /api/v1/core/health
→ 200 {"success": true, "data": {"status": "healthy", "checks": {...}}}
→ 503 {"success": false, "data": {"status": "degraded", "checks": {...}}}
```

### المؤشرات الحيوية للمراقبة
| المؤشر | الوصف | العتبة |
|--------|-------|--------|
| uptime | زمن تشغيل الخادم | < 5 دقائق = إنذار |
| total_transactions | عدد المعاملات اليومي | انخفاض ≥ 50% = إنذار |
| total_volume_fils | حجم التداول اليومي | انخفاض ≥ 50% = إنذار |
| fraud_alerts | تنبيهات الاحتيال | ≥ 10/ساعة = إنذار |
| queue_length | طول طابور المهام | ≥ 100 = إنذار |

### سجلات النظام
```
storage/logs/laravel.log          # السجل الرئيسي
storage/logs/queue.log            # سجل الطابور
storage/logs/failed-jobs.log      # المهام الفاشلة
```

---

## 3. النسخ الاحتياطي والاستعادة

### إنشاء نسخة احتياطية
```bash
# نسخة كاملة (قاعدة بيانات + سجلات التدقيق)
php artisan beza:backup

# قاعدة بيانات فقط
php artisan beza:backup --database

# سجلات تدقيق فقط
php artisan beza:backup --audit

# مسار مخصص
php artisan beza:backup --output=/var/backups/beza
```

### استعادة نسخة احتياطية
```bash
# 1. فك تشفير الملف
BACKUP_FILE="beza-db-2026-06-01_12-00-00.sqlite.enc"
IV_LEN=16
KEY="<BACKUP_ENCRYPTION_KEY>"

# استخراج الـ IV (أول 16 بايت)
dd if="$BACKUP_FILE" of=iv.bin bs=1 count=$IV_LEN

# استخراج المحتوى المشفر
dd if="$BACKUP_FILE" of=ciphertext.bin bs=1 skip=$IV_LEN

# فك التشفير
openssl enc -aes-256-cbc -d -K "$KEY" -iv "$(xxd -p iv.bin)" -in ciphertext.bin -out restored.sqlite

# 2. إيقاف الخادم
php artisan down

# 3. استبدال قاعدة البيانات
cp restored.sqlite database/database.sqlite

# 4. تشغيل الخادم
php artisan up
```

---

## 4. إعادة تشغيل الخدمات

### إعادة تشغيل كاملة
```bash
# إيقاف جميع الخدمات
php artisan down --retry=60

# إعادة تشغيل مشغل الطابور
php artisan queue:restart

# تشغيل الخادم
php artisan up
```

### إعادة تشغيل طابور المهام
```bash
# إعادة تشغيل جميع المشغلين
php artisan queue:restart

# بدء مشغل جديد
php artisan queue:work --tries=3 --backoff=30

# معالجة المهام الفاشلة
php artisan queue:retry all

# حذف جميع المهام الفاشلة
php artisan queue:flush
```

---

## 5. إجراءات الطوارئ

### 5.1 — انقطاع قاعدة البيانات
**الأعراض:** نقطة الصحة ترجع 503 مع `database: unhealthy`
**الإجراء:**
1. التحقق من اتصال الشبكة: `ping <db-host>`
2. التحقق من حالة الخدمة: `systemctl status sqlite`
3. مراجعة السجلات: `tail -100 storage/logs/laravel.log`
4. إعادة تشغيل الخدمة: `php artisan down && php artisan up`
5. إنشاء تقرير في سجل الحوادث

### 5.2 — تراكم المهام في الطابور
**الأعراض:** queue_length ≥ 100 في لوحة المراقبة
**الإجراء:**
1. فحص المهام العالقة: `php artisan queue:failed-table`
2. إعادة تشغيل المشغلين: `php artisan queue:restart`
3. بدء مشغلين إضافيين: `php artisan queue:work --queue=high,default`
4. مراجعة المهام الفاشلة ومعالجتها

### 5.3 — تنبيه احتيال جماعي
**الأعراض:** fraud_alerts ≥ 10/ساعة
**الإجراء:**
1. فتح لوحة الاحتيال: `GET /api/v1/fraud/alerts`
2. مراجعة الأنماط المشبوهة
3. تفعيل قواعد إضافية مؤقتاً
4. إخطار فريق الامتثال
5. توثيق الحادثة

### 5.4 — فشل النسخ الاحتياطي
**الأعراض:** فشل أمر `beza:backup`
**الإجراء:**
1. التحقق من `BACKUP_ENCRYPTION_KEY` في متغيرات البيئة
2. التحقق من صلاحية مسار الإخراج
3. التحقق من مساحة القرص: `df -h`
4. المحاولة يدوياً: `php artisan beza:backup --output=/tmp/backup`
5. إنشاء تذكرة طارئة للفريق التقني

---

## 6. الفحص الصحي

### فحص يدوي
```bash
# نقطة الصحة (عامة)
curl http://localhost:8000/api/v1/core/health

# فحص قاعدة البيانات
php artisan tinker --execute="DB::connection()->getPdo() ? 'OK' : 'FAIL'"

# فحص التخزين
php artisan tinker --execute="Storage::disk('local')->put('test.txt', 'ok') ? 'OK' : 'FAIL'"

# فحص الذاكرة المؤقتة
php artisan tinker --execute="Cache::store('database')->set('ping', true) ? 'OK' : 'FAIL'"

# فحص حالة التطبيق
php artisan about
```

### فحص تلقائي (لكل دقيقة)
```bash
# استخدام cron
* * * * * curl -f http://localhost:8000/api/v1/core/health || echo "Health check failed" | mail -s "CRITICAL" ops@beza.sy
```

---

## 7. إدارة البيئات

### التبديل بين البيئات
```bash
# تطوير
cp .env.development .env
php artisan key:generate
php artisan migrate --force

# تجريبي
cp .env.staging .env
php artisan key:generate
php artisan migrate --force

# إنتاج
cp .env.production .env
php artisan key:generate
php artisan migrate --force
```

### أوامر الصيانة
```bash
# تفعيل وضع الصيانة
php artisan down --secret="<token>" --retry=60

# تعطيل وضع الصيانة
php artisan up

# عرض حالة الصيانة
php artisan down --status
```

---

## 8. جهات الاتصال

| الدور | الاسم | القناة |
|-------|-------|--------|
| قائد التقنية | — | #eng-ledger على Slack |
| مسؤول الامتثال | — | #compliance-ops على Slack |
| فريق الدعم | — | #incident-response على Slack |
| طوارئ تقنية | — | هاتف الطوارئ |

---

## الملحق: سريع

### معرفة الإصدار الحالي
```bash
php artisan --version
cat backend/composer.json | grep version
```

### مشاهدة السجلات الحية
```bash
tail -f storage/logs/laravel.log
```

### تفريغ جميع المهام في الطابور
```bash
php artisan queue:failed-table
php artisan queue:retry all
```

### التحقق من صحة التوقيع (Android)
```bash
keytool -list -v -keystore release.keystore
```
