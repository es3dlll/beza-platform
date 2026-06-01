# 19 - حالات الحافة: امتلاء القرص، رفض الإذن، تعطيل exec في PHP (Edge Cases: Disk Full, Permission Denied, PHP exec Disabled)

<div dir="rtl">

## 1. امتلاء القرص الصلب (Disk Full)

### المشكلة
عند إنشاء نسخة احتياطية، قد لا توجد مساحة كافية على القرص لاستكمال العملية. هذا يؤدي إلى:
- ملف نسخة احتياطية ناقص أو تالف
- فشل mysqldump في منتصف العملية
- تعطل تطبيقات أخرى على نفس الخادم

### المعالجة

```php
/**
 * التحقق من المساحة المتاحة قبل بدء النسخ الاحتياطي
 * 
 * @throws DiskFullException إذا كانت المساحة غير كافية
 */
public function checkDiskSpaceBeforeBackup(): void
{
    $backupPath = storage_path('app/backups');

    // تقدير حجم قاعدة البيانات (مجموع data_length + index_length)
    $estimatedSize = $this->estimateDatabaseSize();

    // المساحة المتاحة
    $freeSpace = disk_free_space($backupPath);

    // نطلب على الأقل 1.5 ضعف الحجم المقدر للأمان
    $requiredSpace = (int)($estimatedSize * 1.5);

    if ($freeSpace < $requiredSpace) {
        // إضافة 10MB كحد أدنى
        $minimumSpace = max($requiredSpace, 10 * 1024 * 1024);

        if ($freeSpace < $minimumSpace) {
            throw new DiskFullException(
                available: (int)$freeSpace,
                required: $minimumSpace
            );
        }
    }
}

/**
 * تقدير حجم قاعدة البيانات
 */
private function estimateDatabaseSize(): int
{
    try {
        $database = config('database.connections.mysql.database');
        $result = \DB::select("
            SELECT SUM(data_length + index_length) AS total_size
            FROM information_schema.tables
            WHERE table_schema = ?
        ", [$database]);

        return (int)($result[0]->total_size ?? 0);
    } catch (\Exception $e) {
        // إذا فشل الاستعلام، نرجع 100MB كتقدير آمن
        return 100 * 1024 * 1024;
    }
}
```

### السيناريوهات

| الحالة | التصرف |
|--------|--------|
| المساحة المتاحة < 10MB | رفض فوري + إشعار عاجل |
| المساحة المتاحة < الحجم التقديري × 1.5 | رفض + اقتراح حذف نسخ قديمة |
| امتلاء القرص أثناء العملية | حذف الملف الجزئي + تسجيل الخطأ |
| المساحة كافية | متابعة العملية بشكل طبيعي |

## 2. رفض الإذن (Permission Denied)

### المشكلة
قد لا يملك مستخدم خادم الويب صلاحيات كافية لتنفيذ بعض العمليات:
- قراءة/كتابة ملفات السجل
- تنفيذ mysqldump
- مسح ملفات الكاش

### المعالجة

```php
/**
 * التحقق من صلاحيات الملفات قبل العمليات
 * 
 * @param string $path مسار الملف أو المجلد
 * @param string $action نوع العملية (read, write, delete)
 * @throws FilePermissionException
 */
public function ensureFilePermissions(string $path, string $action): void
{
    // التحقق من وجود المسار
    if (!file_exists($path)) {
        // إذا كان المسار غير موجود، نتحقق من المجلد الأب
        $parentDir = dirname($path);
        if (!is_dir($parentDir)) {
            throw new FilePermissionException(
                filePath: $parentDir,
                action: 'find',
                message: "المسار غير موجود: {$parentDir}"
            );
        }
    }

    // التحقق من الصلاحيات حسب نوع العملية
    switch ($action) {
        case 'read':
            if (!is_readable($path)) {
                throw new FilePermissionException(
                    filePath: $path,
                    action: 'read',
                    message: "لا يمكن قراءة الملف: {$path}"
                );
            }
            break;

        case 'write':
            if (!is_writable($path)) {
                throw new FilePermissionException(
                    filePath: $path,
                    action: 'write',
                    message: "لا يمكن الكتابة في: {$path}"
                );
            }
            break;

        case 'delete':
            if (!is_writable(dirname($path))) {
                throw new FilePermissionException(
                    filePath: $path,
                    action: 'delete',
                    message: "لا يمكن حذف الملف: {$path}"
                );
            }
            break;

        case 'execute':
            if (!is_executable($path)) {
                throw new FilePermissionException(
                    filePath: $path,
                    action: 'execute',
                    message: "لا يمكن تنفيذ: {$path}"
                );
            }
            break;
    }
}
```

### السيناريوهات الشائعة

```php
// سيناريو: ملف السجل غير قابل للقراءة
// الحل: التحقق من الصلاحيات ومحاولة الإصلاح
public function safeReadLogFile(string $filename): ?string
{
    $filePath = storage_path("logs/{$filename}");

    try {
        $this->ensureFilePermissions($filePath, 'read');
        return file_get_contents($filePath);
    } catch (FilePermissionException $e) {
        // تسجيل التحذير
        Log::warning('فشل قراءة ملف السجل: ' . $e->getMessage());

        // محاولة إصلاح الصلاحيات (إذا كان التطبيق يعمل كـ root)
        if (function_exists('chmod')) {
            @chmod($filePath, 0644);
            if (is_readable($filePath)) {
                return file_get_contents($filePath);
            }
        }

        // إرجاع null للدلالة على الفشل
        return null;
    }
}

// سيناريو: mysqldump غير قابل للتنفيذ
// الحل: التحقق من وجود الأمر في PATH
public function verifyMysqldumpAvailable(): bool
{
    // التحقق من وجود mysqldump في النظام
    $output = [];
    $returnCode = 0;
    exec('which mysqldump 2>&1', $output, $returnCode);

    if ($returnCode !== 0) {
        // محاولة المسار المطلق الشائع
        $commonPaths = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.32\\bin\\mysqldump.exe',
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                // تخزين المسار الصحيح للإستخدام لاحقاً
                config(['system.backup.mysqldump_path' => $path]);
                return true;
            }
        }

        return false;
    }

    return true;
}
```

## 3. تعطيل exec/shell_exec في PHP (PHP exec Disabled)

### المشكلة
في بعض بيئات الاستضافة، يتم تعطيل دوال `exec()` و `shell_exec()` و `system()` لأسباب أمنية. هذا يمنع:
- تشغيل mysqldump للنسخ الاحتياطي
- تشغيل mysql لاستعادة النسخ
- بعض أوامر Artisan التي تستخدم exec

### المعالجة

```php
/**
 * التحقق من توفر دوال exec في PHP
 * 
 * @return bool هل دوال exec متوفرة
 */
public function isExecAvailable(): bool
{
    $disabled = explode(',', ini_get('disable_functions'));

    // التحقق من الدوال المهمة
    $requiredFunctions = ['exec', 'shell_exec', 'system', 'proc_open'];

    $available = [];
    foreach ($requiredFunctions as $func) {
        $available[$func] = function_exists($func) && !in_array($func, $disabled);
    }

    return $available;
}

/**
 * بديل باستخدام Symfony Process إذا كانت exec غير متوفرة
 * ولكن Proc_open لا يزال متاحاً
 */
public function runCommandSafely(string $command, int $timeout = 300): array
{
    $execAvailable = $this->isExecAvailable();

    if ($execAvailable['exec']) {
        // استخدام exec المباشر
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        return [
            'success' => $returnCode === 0,
            'output'  => implode("\n", $output),
        ];
    }

    if ($execAvailable['proc_open']) {
        // استخدام proc_open كبديل
        return $this->runWithProcOpen($command, $timeout);
    }

    // لا توجد طريقة لتشغيل الأوامر الخارجية
    throw new \RuntimeException(
        'لا يمكن تشغيل الأوامر الخارجية. '
        . 'الرجاء تمكين دالة exec() أو proc_open() في إعدادات PHP.'
    );
}

/**
 * تشغيل أمر باستخدام proc_open
 */
private function runWithProcOpen(string $command, int $timeout): array
{
    $descriptors = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w'],  // stderr
    ];

    $process = proc_open($command, $descriptors, $pipes);

    if (!is_resource($process)) {
        throw new \RuntimeException('فشل فتح العملية');
    }

    // إغلاق stdin
    fclose($pipes[0]);

    // قراءة stdout و stderr مع مهلة
    $stdout = '';
    $stderr = '';
    $startTime = time();

    while (true) {
        if (time() - $startTime > $timeout) {
            proc_terminate($process, 9);
            throw new \RuntimeException('انتهت المهلة الزمنية للعملية');
        }

        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }

        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        usleep(100000); // 100ms
    }

    // قراءة الباقي
    $stdout .= stream_get_contents($pipes[1]);
    $stderr .= stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $returnCode = proc_close($process);

    return [
        'success' => $returnCode === 0,
        'output'  => $stdout,
        'error'   => $stderr,
    ];
}
```

## 4. هجمات Directory Traversal

### المشكلة
عرض ملفات السجل باستخدام أسماء الملفات قد يسمح بهجمات directory traversal.

```php
/**
 * التحقق الصارم من أسماء الملفات
 * لمنع هجمات directory traversal
 * 
 * @param string $filename اسم الملف المدخل
 * @return bool هل الاسم آمن
 */
public function isFilenameSafe(string $filename): bool
{
    // 1. منع المسارات المطلقة
    if (str_starts_with($filename, '/') || str_starts_with($filename, '\\')) {
        return false;
    }

    // 2. منع الـ directory traversal
    if (str_contains($filename, '..')) {
        return false;
    }

    // 3. السماح فقط بالأحرف الآمنة
    if (!preg_match('/^[a-zA-Z0-9_\-]+\.log$/', $filename)) {
        return false;
    }

    // 4. منع الأسماء الطويلة جداً
    if (strlen($filename) > 100) {
        return false;
    }

    return true;
}
```

## 5. التزامن (Concurrency)

### المشكلة
تشغيل عمليتين متطابقتين في نفس الوقت (مثل نسختين احتياطيتين).

```php
/**
 * نظام القفل المتقدم باستخدام ملف lock
 * مع التحقق من انتهاء صلاحية القفل
 */
class BackupLock
{
    private string $lockFile;
    private int $maxLockDuration = 1800; // 30 دقيقة

    public function __construct()
    {
        $this->lockFile = storage_path('app/backups/.backup_lock');
    }

    /**
     * محاولة الحصول على القفل
     * 
     * @throws \RuntimeException إذا كان القفل موجوداً
     */
    public function acquire(): void
    {
        if (file_exists($this->lockFile)) {
            $lockTime = file_get_contents($this->lockFile);
            $lockTimestamp = strtotime($lockTime);

            if ($lockTimestamp === false) {
                // ملف تالف، نحذفه ونستمر
                unlink($this->lockFile);
            } elseif (time() - $lockTimestamp < $this->maxLockDuration) {
                throw new \RuntimeException(
                    'يوجد نسخة احتياطية قيد التشغيل حالياً.'
                );
            } else {
                // القفل قديم (أكثر من 30 دقيقة)، نتجاهله
                Log::warning('تم العثور على قفل قديم للنسخ الاحتياطي. تم تجاهله.');
                unlink($this->lockFile);
            }
        }

        file_put_contents($this->lockFile, now()->toIso8601String());
    }

    public function release(): void
    {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }
}
```

## 6. ملفات السجل الكبيرة جداً

### المشكلة
ملفات السجل قد تصل إلى أحجام كبيرة (GB)، مما يؤدي إلى:
- استهلاك الذاكرة عند قراءة الملف كاملاً
- مهلة الاستجابة (timeout)

```php
/**
 * قراءة ذكية لملفات السجل الكبيرة
 */
public function readLargeLogFile(string $filePath, int $maxLines = 100): string
{
    $fileSize = filesize($filePath);

    if ($fileSize < 1024 * 1024) {
        // ملف صغير (< 1MB): نقرأه كاملاً
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        return implode("\n", array_slice($lines, -$maxLines));
    }

    // ملف كبير: نستخدم القراءة من النهاية
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        throw new \RuntimeException('لا يمكن فتح ملف السجل');
    }

    fseek($handle, 0, SEEK_END);
    $position = ftell($handle);

    $lines = [];
    $currentLine = '';

    for ($i = $position - 1; $i >= 0 && count($lines) <= $maxLines; $i--) {
        fseek($handle, $i);
        $char = fgetc($handle);

        if ($char === "\n") {
            $lines[] = strrev($currentLine);
            $currentLine = '';
        } else {
            $currentLine .= $char;
        }
    }

    fclose($handle);

    // إضافة آخر سطر
    if ($currentLine !== '') {
        $lines[] = strrev($currentLine);
    }

    return implode("\n", array_reverse($lines));
}
```

## ملخص حالات الحافة

| الحالة | التأثير | مستوى الخطورة | المعالجة |
|--------|---------|--------------|---------|
| امتلاء القرص | فشل النسخ الاحتياطي | عالي | التحقق المسبق + تنظيف تلقائي |
| رفض الإذن | فشل عمليات الملفات | عالي | التحقق + محاولة الإصلاح |
| تعطيل exec | فشل النسخ والاستعادة | عالي | proc_open كبديل + رسالة واضحة |
| Directory Traversal | ثغرة أمنية | حرج | التحقق الصارم من أسماء الملفات |
| التزامن | نسخ احتياطية متداخلة | متوسط | نظام القفل مع انتهاء الصلاحية |
| ملفات كبيرة | استهلاك ذاكرة | متوسط | القراءة من النهاية (tail) |
| قفل قديم | تعطل العمليات | منخفض | انتهاء صلاحية القفل تلقائياً |
| mysqldump غير موجود | تعطل النسخ | عالي | التحقق + البحث في المسارات الشائعة |

</div>
