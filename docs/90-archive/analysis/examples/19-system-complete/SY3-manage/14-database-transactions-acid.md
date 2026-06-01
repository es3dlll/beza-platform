# 14 - معاملات قاعدة البيانات: لا توجد معاملات، لكن النسخ الاحتياطي يستخدم --single-transaction (Database Transactions ACID)

<div dir="rtl">

## نظرة عامة

SY3-manage لا تستخدم معاملات قاعدة البيانات التقليدية (ACID transactions) لأنها:

1. **لا تكتب في قاعدة البيانات**: معظم العمليات تتم عبر Artisan أو exec أو نظام الملفات
2. **تتعامل مع موارد خارجية**: الملفات، أوامر shell، عمليات النظام
3. **العمليات غير ذرية**: كل عملية إدارة هي إجراء مستقل

## كيف تتعامل SY3-manage مع التناسق؟

بدلاً من معاملات قاعدة البيانات، تستخدم SY3-manage استراتيجيات بديلة لضمان التناسق:

### 1. آلية القفل (File Locking) للنسخ الاحتياطي

```php
// استخدام ملف قفل لمنع تنفيذ نسختين احتياطيتين في نفس الوقت
$lockFile = storage_path('app/backups/.backup_lock');

if (file_exists($lockFile)) {
    // التحقق من أن القفل ليس قديماً (أكثر من 30 دقيقة)
    $lockTime = file_get_contents($lockFile);
    if (strtotime($lockTime) > now()->subMinutes(30)->timestamp) {
        throw new \RuntimeException('يوجد نسخة احتياطية قيد التشغيل حالياً');
    }
}

// إنشاء القفل
file_put_contents($lockFile, now()->toIso8601String());

try {
    // تنفيذ عملية النسخ الاحتياطي
    // ...
} finally {
    // ضمان حذف القفل حتى في حالة الفشل
    if (file_exists($lockFile)) {
        @unlink($lockFile);
    }
}
```

### 2. --single-transaction في mysqldump

```php
/**
 * خيار --single-transaction هو المفتاح لضمان تناسق البيانات
 * دون قفل الجداول، وبدون استخدام معاملات Laravel
 * 
 * كيف يعمل:
 * 1. يبدأ قراءة متسقة (consistent read) في بداية الأمر
 * 2. يقرأ جميع الجداول في نفس النقطة الزمنية
 * 3. لا يؤثر على عمليات الكتابة الجارية
 * 4. مناسب لقواعد بيانات InnoDB (وليس MyISAM)
 */
$command = sprintf(
    'mysqldump --host=%s --user=%s --password=%s %s %s',
    escapeshellarg($host),
    escapeshellarg($username),
    escapeshellarg($password),
    '--single-transaction --routines --events --triggers',
    escapeshellarg($database)
);

// المزايا:
// - تناسق: جميع البيانات مأخوذة في لحظة زمنية واحدة
// - أداء: لا يقفل الجداول
// - توفر: التطبيق يبقى متاحاً للقراءة والكتابة
// - أمان: لا تأثير على المستخدمين النشطين
```

### 3. العملية غير القابلة للتقسيم (Atomic Operation) لمسح الكاش

```php
/**
 * مسح الكاش هو عملية غير قابلة للتقسيم من منظور التطبيق
 * لكنها تتكون من خطوات متعددة داخلياً
 * 
 * الترتيب مهم:
 * 1. مسح كاش الإعدادات أولاً (config)
 * 2. مسح كاش المسارات (route)
 * 3. مسح كاش القوالب (view)
 * 4. مسح كاش التطبيق (application)
 * 
 * إذا فشلت خطوة، نستمر في الخطوات التالية ولا نتراجع
 * لأن التراجع قد يترك النظام في حالة غير متسقة
 */
public function clear(): array
{
    $results = [];
    $results['config'] = $this->runCommand('config:clear');
    $results['route']  = $this->runCommand('route:clear');
    $results['view']   = $this->runCommand('view:clear');
    $results['application'] = $this->runCommand('cache:clear');
    return $results;
    // لا يوجد تراجع (rollback) - نستمر حتى آخر خطوة
}
```

### 4. التحقق من سلامة الملفات (File Integrity Check)

```php
/**
 * بعد إنشاء نسخة احتياطية، نتحقق من سلامة الملف
 * هذا يشبه التحقق من الـ COMMIT في قاعدة البيانات
 */
public function verifyBackupIntegrity(string $filePath): bool
{
    // 1. التحقق من وجود الملف
    if (!file_exists($filePath)) {
        return false;
    }

    // 2. التحقق من أن الملف ليس فارغاً
    if (filesize($filePath) === 0) {
        return false;
    }

    // 3. التحقق من توقيع gzip
    $handle = fopen($filePath, 'rb');
    $magicBytes = fread($handle, 2);
    fclose($handle);

    // gzip magic bytes: 0x1F 0x8B
    if ($magicBytes !== "\x1f\x8b") {
        return false;
    }

    // 4. محاولة فك ضغط جزء صغير للتحقق من السلامة
    try {
        $testContent = gzfile($filePath);
        if ($testContent === false || count($testContent) < 2) {
            return false;
        }
    } catch (\Exception $e) {
        return false;
    }

    return true;
}
```

### 5. معالجة الفشل (Failure Handling)

على عكس معاملات قاعدة البيانات حيث يمكن عمل ROLLBACK، هنا نستخدم:

```php
/**
 * استراتيجية "التنظيف عند الفشل"
 * بدلاً من التراجع، نقوم بتنظيف الآثار
 */
try {
    // إنشاء النسخة الاحتياطية
    $result = $this->backupManager->create();
    event(new BackupCreated(...));
    return success($result);
} catch (\Exception $e) {
    // تنظيف: حذف الملف الجزئي إن وجد
    if (isset($filePath) && file_exists($filePath)) {
        @unlink($filePath);
    }

    // تسجيل الحدث
    event(new BackupFailed(...));
    
    // إعادة رمي الخطأ للمعالجة في المتحكم
    throw $e;
}
```

## مقارنة مع ACID

| خاصية | معاملات قاعدة البيانات | SY3-manage |
|-------|----------------------|------------|
| **Atomicity** | كل شيء ينجح أو كل شيء يفشل | كل عملية مستقلة؛ إذا فشلت إحدى خطوات مسح الكاش، نستمر |
| **Consistency** | تظل البيانات صحيحة دائماً | --single-transaction يضمن لقطة متسقة |
| **Isolation** | العمليات معزولة عن بعضها | ملف القفل يمنع التشغيل المتزامن |
| **Durability** | التغييرات دائمة بعد الـ COMMIT | الملفات تبقى على القرص بعد الإنشاء |

## استراتيجية التناسق

```
نسخ احتياطي:
┌─────────────────────────────────────────────────────────┐
│ 1. تقدير المساحة المطلوبة                               │
│ 2. التحقق من المساحة المتاحة                             │
│ 3. إنشاء ملف القفل (Lock)                               │
│ 4. تشغيل mysqldump --single-transaction                  │
│ 5. توجيه الإخراج إلى gzip → ملف.sql.gz                   │
│ 6. التحقق من سلامة الملف (Integrity Check)               │
│ 7. حذف ملف القفل (Unlock)                                │
│ 8. إطلاق حدث النجاح/الفشل                                │
└─────────────────────────────────────────────────────────┘
         ↓
إذا فشلت أي خطوة:
  ← حذف الملف الجزئي
  ← حذف القفل
  ← تسجيل الخطأ
  ← إطلاق حدث الفشل

مسح الكاش:
┌─────────────────────────────────────────────────────────┐
│ 1. config:clear                                          │
│ 2. route:clear                                           │
│ 3. view:clear                                            │
│ 4. cache:clear                                           │
└─────────────────────────────────────────────────────────┘
         ↓
إذا فشلت أي خطوة:
  ← تسجيل الخطأ
  ← الاستمرار مع الخطوات التالية
  ← إرجاع النتائج (بعضها قد يكون فاشلاً)
```

## الخلاصة

SY3-manage لا تحتاج معاملات قاعدة البيانات لأنها تتعامل مع موارد نظام وليس بيانات تطبيق. بدلاً من ذلك، تستخدم:

1. **--single-transaction**: للحصول على لقطة متسقة لقاعدة البيانات أثناء النسخ الاحتياطي
2. **ملف القفل (Lock File)**: لمنع التشغيل المتزامن للنسخ الاحتياطية
3. **التحقق من السلامة (Integrity Check)**: التأكد من صحة الملفات المنشأة
4. **التنظيف عند الفشل (Cleanup on Failure)**: حذف الآثار الجزئية للعمليات الفاشلة
5. **التسجيل والأحداث**: تتبع جميع العمليات للتدقيق والمراجعة

</div>
