# 13 - معالجة الاستثناءات: فشل أوامر Artisan، امتلاء القرص أثناء النسخ الاحتياطي (Exception Handling)

<div dir="rtl">

## نظرة عامة

SY3-manage تتعامل مع العديد من العمليات التي قد تفشل لأسباب متعددة. يجب معالجة كل نوع فشل بشكل مناسب لتوفير تغذية راجعة واضحة للمستخدم وحماية استقرار النظام.

## أنواع الاستثناءات

### 1. فشل أوامر Artisan

```php
<?php
// app/Exceptions/System/ArtisanCommandException.php

namespace App\Exceptions\System;

use Exception;

/**
 * استثناء فشل أمر Artisan
 * يرمى عندما يفشل تنفيذ أمر Artisan (cache:clear, queue:restart, etc)
 */
class ArtisanCommandException extends Exception
{
    /**
     * اسم الأمر الذي فشل
     */
    public string $command;

    /**
     * إخراج الأمر (حتى لو كان خطأ)
     */
    public string $output;

    /**
     * @param string $command اسم الأمر
     * @param string $output إخراج الأمر
     * @param string $message رسالة الخطأ المخصصة
     */
    public function __construct(
        string $command,
        string $output = '',
        string $message = ''
    ) {
        $this->command = $command;
        $this->output  = $output;

        $message = $message ?: "فشل تنفيذ الأمر: {$command}";

        parent::__construct($message);
    }

    /**
     * تقديم الاستثناء كاستجابة HTTP
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data'    => [
                'command' => $this->command,
                'output'  => $this->output,
            ],
        ], 500);
    }
}
```

### 2. فشل النسخ الاحتياطي

```php
<?php
// app/Exceptions/System/BackupException.php

namespace App\Exceptions\System;

use Exception;

/**
 * استثناء فشل النسخ الاحتياطي
 * يرمى عندما تفشل عملية إنشاء أو استعادة أو حذف نسخة احتياطية
 */
class BackupException extends Exception
{
    /**
     * نوع العملية التي فشلت
     */
    public string $operation; // create, restore, delete

    /**
     * اسم الملف المعني
     */
    public string $filename;

    /**
     * @param string $operation العملية
     * @param string $filename اسم الملف
     * @param string $message رسالة الخطأ
     * @param int $code رمز الخطأ
     */
    public function __construct(
        string $operation,
        string $filename,
        string $message = '',
        int $code = 0,
    ) {
        $this->operation = $operation;
        $this->filename  = $filename;
        $message = $message ?: "فشلت عملية {$operation} للملف: {$filename}";

        parent::__construct($message, $code);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data'    => [
                'operation' => $this->operation,
                'filename'  => $this->filename,
            ],
        ], 500);
    }
}
```

### 3. استثناء امتلاء القرص

```php
<?php
// app/Exceptions/System/DiskFullException.php

namespace App\Exceptions\System;

use Exception;

/**
 * استثناء امتلاء القرص
 * يرمى عندما لا توجد مساحة كافية على القرص للنسخ الاحتياطي
 */
class DiskFullException extends Exception
{
    /**
     * المساحة المتاحة بالبايت
     */
    public int $availableSpace;

    /**
     * المساحة المطلوبة بالبايت
     */
    public int $requiredSpace;

    /**
     * @param int $available المساحة المتاحة
     * @param int $required المساحة المطلوبة
     */
    public function __construct(int $available, int $required)
    {
        $this->availableSpace = $available;
        $this->requiredSpace  = $required;

        $availableFormatted = $this->formatBytes($available);
        $requiredFormatted  = $this->formatBytes($required);

        $message = "لا توجد مساحة كافية على القرص. المتاح: {$availableFormatted}, المطلوب: {$requiredFormatted}";

        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data'    => [
                'available_space' => $this->availableSpace,
                'required_space'  => $this->requiredSpace,
                'disk_free'       => disk_free_space(storage_path()),
            ],
        ], 507); // 507 Insufficient Storage
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 3) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

### 4. استثناء صلاحية الملفات

```php
<?php
// app/Exceptions/System/FilePermissionException.php

namespace App\Exceptions\System;

use Exception;

/**
 * استثناء صلاحية الملفات
 * يرمى عندما لا يملك التطبيق الصلاحيات الكافية لقراءة أو كتابة أو حذف ملف
 */
class FilePermissionException extends Exception
{
    public string $filePath;
    public string $action; // read, write, delete

    public function __construct(string $filePath, string $action, string $message = '')
    {
        $this->filePath = $filePath;
        $this->action   = $action;

        $message = $message ?: "لا يمكن {$action} الملف: {$filePath}. تحقق من الصلاحيات.";

        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data'    => [
                'file'   => $this->filePath,
                'action' => $this->action,
            ],
        ], 403);
    }
}
```

## معالج الاستثناءات العام

```php
<?php
// app/Exceptions/Handler.php (إضافة للـ Handler الموجود)

namespace App\Exceptions;

use App\Exceptions\System\ArtisanCommandException;
use App\Exceptions\System\BackupException;
use App\Exceptions\System\DiskFullException;
use App\Exceptions\System\FilePermissionException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * تسجيل عمليات رد الاتصال المخصصة للاستثناءات
     */
    public function register(): void
    {
        // معالجة استثناءات Artisan
        $this->reportable(function (ArtisanCommandException $e) {
            \Illuminate\Support\Facades\Log::channel('admin')->error('فشل أمر Artisan', [
                'command' => $e->command,
                'output'  => $e->output,
                'message' => $e->getMessage(),
            ]);
        });

        // معالجة استثناءات النسخ الاحتياطي
        $this->reportable(function (BackupException $e) {
            \Illuminate\Support\Facades\Log::channel('admin')->error('فشل النسخ الاحتياطي', [
                'operation' => $e->operation,
                'filename'  => $e->filename,
            ]);
        });

        // معالجة استثناءات امتلاء القرص
        $this->reportable(function (DiskFullException $e) {
            \Illuminate\Support\Facades\Log::channel('admin')->critical('امتلاء القرص', [
                'available' => $e->availableSpace,
                'required'  => $e->requiredSpace,
            ]);

            // إرسال إشعار عاجل للمشرفين
            // Notification::route('mail', config('app.admin_email'))
            //     ->notify(new DiskFullAlert(...));
        });

        // معالجة استثناءات صلاحية الملفات
        $this->reportable(function (FilePermissionException $e) {
            \Illuminate\Support\Facades\Log::channel('admin')->warning('خطأ صلاحية ملف', [
                'file'   => $e->filePath,
                'action' => $e->action,
            ]);
        });
    }
}
```

## معالجة الأخطاء في BackupManager

```php
<?php
// استخراج من BackupManager يوضح معالجة الأخطاء

public function create(): array
{
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql.gz';
    $filePath = $this->backupPath . '/' . $filename;

    // 1. التحقق من المساحة المتاحة قبل البدء
    $estimatedSize = $this->estimateBackupSize();
    $freeSpace = disk_free_space($this->backupPath);

    if ($freeSpace < $estimatedSize * 1.5) { // 50% مساحة إضافية كعامل أمان
        throw new DiskFullException(
            available: $freeSpace,
            required: (int)($estimatedSize * 1.5)
        );
    }

    // 2. إنشاء ملف القفل
    $lockFile = $this->backupPath . '/.backup_lock';
    if (file_exists($lockFile)) {
        // التحقق من أن القفل ليس قديماً (أكثر من 30 دقيقة)
        $lockTime = file_get_contents($lockFile);
        if (strtotime($lockTime) > now()->subMinutes(30)->timestamp) {
            throw new \RuntimeException('يوجد نسخة احتياطية قيد التشغيل');
        }
        // إذا كان القفل قديماً، نستمر ونتجاهله
    }
    file_put_contents($lockFile, now()->toIso8601String());

    try {
        // 3. تنفيذ أمر mysqldump
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --single-transaction %s 2>&1',
            escapeshellarg(config('database.connections.mysql.host')),
            escapeshellarg(config('database.connections.mysql.username')),
            escapeshellarg(config('database.connections.mysql.password')),
            escapeshellarg(config('database.connections.mysql.database'))
        );

        $output = [];
        $returnCode = 0;
        exec($command . ' | gzip > ' . escapeshellarg($filePath), $output, $returnCode);

        // 4. التحقق من نتيجة التنفيذ
        if ($returnCode !== 0) {
            throw new BackupException(
                operation: 'create',
                filename: $filename,
                message: 'فشل تنفيذ mysqldump (رمز الخطأ: ' . $returnCode . ')'
            );
        }

        // 5. التحقق من صحة الملف
        if (!file_exists($filePath) || filesize($filePath) === 0) {
            throw new BackupException(
                operation: 'create',
                filename: $filename,
                message: 'ملف النسخة الاحتياطية فارغ أو لم يتم إنشاؤه'
            );
        }

        return [
            'filename' => $filename,
            'size'     => filesize($filePath),
            'size_formatted' => $this->formatSize(filesize($filePath)),
            'created_at' => date('Y-m-d H:i:s'),
        ];

    } catch (BackupException $e) {
        // حذف الملف إذا تم إنشاؤه جزئياً
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        throw $e;
    } catch (\Throwable $e) {
        // حذف الملف لأي خطأ آخر
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        throw new BackupException(
            operation: 'create',
            filename: $filename,
            message: 'خطأ غير متوقع: ' . $e->getMessage(),
        );
    } finally {
        // حذف ملف القفل
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }
    }
}

/**
 * تقدير حجم النسخة الاحتياطية (استعلام تقريبي)
 */
private function estimateBackupSize(): int
{
    try {
        $dbName = config('database.connections.mysql.database');
        $result = \DB::select("
            SELECT SUM(data_length + index_length) AS total_size
            FROM information_schema.tables
            WHERE table_schema = ?
        ", [$dbName]);

        return (int)($result[0]->total_size ?? 0);
    } catch (\Exception $e) {
        // إذا فشل الاستعلام، نفترض 100MB
        return 100 * 1024 * 1024;
    }
}
```

## تسجيل الأخطاء في سجل المشرفين

```php
<?php
// config/logging.php
// إضافة قناة تسجيل خاصة بالمشرفين

return [
    'channels' => [
        // قناة عادية (موجودة مسبقاً)
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
        ],

        // قناة خاصة بالمشرفين لأغراض التدقيق
        'admin' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/admin.log'),
            'level'  => 'debug',
            'days'   => 90, // الاحتفاظ بالسجلات لمدة 90 يوماً
        ],

        // قناة للنسخ الاحتياطي
        'backup' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/backup.log'),
            'level'  => 'debug',
            'days'   => 365, // الاحتفاظ بسجلات النسخ الاحتياطي لمدة سنة
        ],
    ],
];
```

## ملخص معالجة الاستثناءات

| الاستثناء | متى يحدث | رمز HTTP | التصرف |
|-----------|---------|---------|--------|
| ArtisanCommandException | فشل أمر Artisan | 500 | تسجيل + إرجاع رسالة خطأ |
| BackupException | فشل نسخ احتياطي | 500 | حذف الملف الجزئي + تسجيل + إرجاع رسالة |
| DiskFullException | امتلاء القرص | 507 | إشعار عاجل + إرجاع رسالة |
| FilePermissionException | صلاحية ملف | 403 | تسجيل تحذير + إرجاع رسالة |
| RuntimeException | أخطاء عامة | 500 | تسجيل + إرجاع رسالة |
| ProcessFailedException | فشل Symfony Process | 500 | تسجيل + إرجاع رسالة |

</div>
