# 10 - طبقة الخدمات المساعدة: BackupManager و SystemInfoCollector (Auxiliary Service Layer)

<div dir="rtl">

## BackupManager

```php
<?php
// app/Services/System/BackupManager.php

namespace App\Services\System;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * مدير النسخ الاحتياطي
 * مسؤول عن إنشاء وإدارة واستعادة النسخ الاحتياطية لقاعدة البيانات
 * يستخدم mysqldump كأداة أساسية للنسخ الاحتياطي
 */
class BackupManager
{
    /**
     * المسار الذي يتم تخزين النسخ الاحتياطية فيه
     */
    private string $backupPath;

    /**
     * المهلة الزمنية القصوى لعملية النسخ الاحتياطي (بالثواني)
     */
    private int $timeout = 300; // 5 دقائق

    public function __construct()
    {
        // تحديد مسار تخزين النسخ الاحتياطية
        $this->backupPath = storage_path('app/backups');

        // إنشاء مجلد النسخ الاحتياطية إذا لم يكن موجوداً
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * إنشاء نسخة احتياطية جديدة لقاعدة البيانات
     * يستخدم mysqldump مع --single-transaction لضمان تناسق البيانات
     * 
     * @return array معلومات عن النسخة الاحتياطية المنشأة
     */
    public function create(): array
    {
        // توليد اسم فريد للملف باستخدام التاريخ والوقت
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql.gz';
        $filePath = $this->backupPath . '/' . $filename;

        // إنشاء ملف قفل لمنع تشغيل عمليات نسخ متعددة في نفس الوقت
        $lockFile = $this->backupPath . '/.backup_lock';
        file_put_contents($lockFile, date('Y-m-d H:i:s'));

        try {
            // الحصول على إعدادات قاعدة البيانات من ملف config/database.php
            $dbConfig = config('database.connections.mysql');

            // بناء أمر mysqldump مع خيارات الأمان والأداء
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --events --triggers --databases %s | gzip > %s',
                escapeshellarg($dbConfig['host']),
                escapeshellarg($dbConfig['port'] ?? 3306),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($filePath)
            );

            // تنفيذ الأمر باستخدام Symfony Process للأمان والتحكم
            $process = Process::fromShellCommandline($command);
            $process->setTimeout($this->timeout);
            $process->run();

            // التحقق من نجاح التنفيذ
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // التحقق من صحة الملف المنشأ
            if (!file_exists($filePath) || filesize($filePath) === 0) {
                throw new \RuntimeException('فشل إنشاء ملف النسخة الاحتياطية');
            }

            // إعادة معلومات النسخة الاحتياطية
            return [
                'filename'       => $filename,
                'path'           => $filePath,
                'size'           => filesize($filePath),
                'size_formatted' => $this->formatSize(filesize($filePath)),
                'created_at'     => date('Y-m-d H:i:s'),
            ];
        } finally {
            // حذف ملف القفل بعد الانتهاء (نجاحاً أو فشلاً)
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
    }

    /**
     * عرض قائمة بجميع النسخ الاحتياطية المتاحة
     * 
     * @return array قائمة النسخ الاحتياطية مع التفاصيل
     */
    public function list(): array
    {
        $backups = [];

        // البحث عن جميع ملفات النسخ الاحتياطي
        $files = glob($this->backupPath . '/backup_*.sql.gz');

        foreach ($files as $file) {
            $backups[] = [
                'filename'       => basename($file),
                'size'           => filesize($file),
                'size_formatted' => $this->formatSize(filesize($file)),
                'created_at'     => date('Y-m-d H:i:s', filectime($file)),
                'modified_at'    => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        // ترتيب النسخ من الأحدث إلى الأقدم
        usort($backups, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return $backups;
    }

    /**
     * استعادة قاعدة البيانات من نسخة احتياطية
     * 
     * @param string $id اسم ملف النسخة الاحتياطية
     * @return array نتيجة عملية الاستعادة
     */
    public function restore(string $id): array
    {
        $filePath = $this->backupPath . '/' . $id;

        // التحقق من وجود الملف
        if (!file_exists($filePath)) {
            throw new \RuntimeException('ملف النسخة الاحتياطية غير موجود');
        }

        // الحصول على إعدادات قاعدة البيانات
        $dbConfig = config('database.connections.mysql');

        // بناء أمر استعادة قاعدة البيانات
        $command = sprintf(
            'gunzip -c %s | mysql --host=%s --port=%s --user=%s --password=%s %s',
            escapeshellarg($filePath),
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['port'] ?? 3306),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['database'])
        );

        // تنفيذ الأمر
        $process = Process::fromShellCommandline($command);
        $process->setTimeout($this->timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return [
            'success'  => true,
            'message'  => "تم استعادة قاعدة البيانات من {$id} بنجاح",
            'filename' => $id,
            'restored_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * حذف نسخة احتياطية
     * 
     * @param string $id اسم ملف النسخة الاحتياطية
     * @return array نتيجة الحذف
     */
    public function delete(string $id): array
    {
        $filePath = $this->backupPath . '/' . $id;

        // التحقق من وجود الملف
        if (!file_exists($filePath)) {
            throw new \RuntimeException('ملف النسخة الاحتياطية غير موجود');
        }

        // التحقق من إمكانية الحذف
        if (!is_writable($filePath)) {
            throw new \RuntimeException('لا يمكن حذف الملف. تحقق من الصلاحيات.');
        }

        // حذف الملف
        if (!unlink($filePath)) {
            throw new \RuntimeException('فشل حذف ملف النسخة الاحتياطية');
        }

        return [
            'success'   => true,
            'message'   => "تم حذف النسخة الاحتياطية {$id} بنجاح",
            'filename'  => $id,
            'deleted_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * تنسيق حجم الملف
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

## SystemInfoCollector

```php
<?php
// app/Services/System/SystemInfoCollector.php

namespace App\Services\System;

/**
 * جامع معلومات النظام
 * مسؤول عن جمع معلومات شاملة عن النظام وبيئة التطبيق
 */
class SystemInfoCollector
{
    /**
     * جمع جميع معلومات النظام
     * 
     * @return array معلومات النظام الكاملة
     */
    public function collect(): array
    {
        return [
            'php'           => $this->getPhpInfo(),
            'laravel'       => $this->getLaravelInfo(),
            'environment'   => $this->getEnvironmentInfo(),
            'server'        => $this->getServerInfo(),
            'database'      => $this->getDatabaseInfo(),
            'disk'          => $this->getDiskInfo(),
            'memory'        => $this->getMemoryInfo(),
            'extensions'    => $this->getLoadedExtensions(),
        ];
    }

    /**
     * معلومات PHP
     * 
     * @return array إصدار PHP والإعدادات المهمة
     */
    private function getPhpInfo(): array
    {
        return [
            'version'            => phpversion(),
            'sapi'               => php_sapi_name(),
            'max_execution_time' => ini_get('max_execution_time') . ' ثانية',
            'memory_limit'       => ini_get('memory_limit'),
            'upload_max_filesize'=> ini_get('upload_max_filesize'),
            'post_max_size'      => ini_get('post_max_size'),
            'date_timezone'      => date_default_timezone_get(),
            'display_errors'     => ini_get('display_errors'),
            'error_reporting'    => $this->getErrorReportingLevel(),
            'opcache_enabled'    => function_exists('opcache_get_status') && opcache_get_status()['opcache_enabled'] ?? false,
        ];
    }

    /**
     * معلومات Laravel
     * 
     * @return array إصدار Laravel والإعدادات
     */
    private function getLaravelInfo(): array
    {
        return [
            'version'         => app()->version(),
            'environment'     => app()->environment(),
            'debug_mode'      => config('app.debug'),
            'locale'          => config('app.locale'),
            'url'             => config('app.url'),
            'cache_driver'    => config('cache.default'),
            'queue_driver'    => config('queue.default'),
            'session_driver'  => config('session.driver'),
            'log_channel'     => config('logging.default'),
        ];
    }

    /**
     * معلومات البيئة
     * 
     * @return array معلومات عن البيئة الحالية
     */
    private function getEnvironmentInfo(): array
    {
        return [
            'app_env'    => app()->environment(),
            'app_debug'  => config('app.debug'),
            'jwt_ttl'    => config('jwt.ttl') . ' دقيقة',
            'timezone'   => config('app.timezone'),
            'maintenance'=> app()->isDownForMaintenance(),
        ];
    }

    /**
     * معلومات الخادم
     * 
     * @return array معلومات عن الخادم
     */
    private function getServerInfo(): array
    {
        return [
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'غير معروف',
            'server_name'     => $_SERVER['SERVER_NAME'] ?? gethostname(),
            'server_ip'       => $_SERVER['SERVER_ADDR'] ?? 'غير معروف',
            'client_ip'       => request()->ip(),
            'protocol'        => $_SERVER['SERVER_PROTOCOL'] ?? 'غير معروف',
            'port'            => $_SERVER['SERVER_PORT'] ?? 'غير معروف',
        ];
    }

    /**
     * معلومات قاعدة البيانات
     * 
     * @return array معلومات عن اتصال قاعدة البيانات
     */
    private function getDatabaseInfo(): array
    {
        try {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            $info = [
                'connection' => $connection,
                'driver'     => $driver,
                'host'       => config("database.connections.{$connection}.host"),
                'port'       => config("database.connections.{$connection}.port"),
                'database'   => config("database.connections.{$connection}.database"),
            ];

            // إذا كان MySQL، نحصل على إضافات مثل الإصدار
            if ($driver === 'mysql') {
                $pdo = \DB::connection()->getPdo();
                $info['mysql_version'] = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
                $info['charset'] = config("database.connections.{$connection}.charset");
            }

            return $info;
        } catch (\Exception $e) {
            return [
                'error' => 'غير قادر على الاتصال بقاعدة البيانات: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * معلومات القرص الصلب
     * 
     * @return array معلومات المساحة المستخدمة والمتاحة
     */
    private function getDiskInfo(): array
    {
        $basePath = base_path();

        return [
            'total_space'     => $this->formatBytes(disk_total_space($basePath)),
            'free_space'      => $this->formatBytes(disk_free_space($basePath)),
            'used_space'      => $this->formatBytes(disk_total_space($basePath) - disk_free_space($basePath)),
            'usage_percent'   => round(
                (disk_total_space($basePath) - disk_free_space($basePath))
                / disk_total_space($basePath) * 100, 2
            ),
            'storage_path'    => storage_path(),
        ];
    }

    /**
     * معلومات الذاكرة
     * 
     * @return array معلومات استخدام الذاكرة
     */
    private function getMemoryInfo(): array
    {
        $memoryLimit = ini_get('memory_limit');

        return [
            'memory_limit'  => $memoryLimit,
            'memory_usage'  => $this->formatBytes(memory_get_usage(true)),
            'memory_peak'   => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }

    /**
     * الإضافات المحملة في PHP
     * 
     * @return array قائمة الإضافات مع إصداراتها
     */
    private function getLoadedExtensions(): array
    {
        $extensions = get_loaded_extensions();
        $result = [];

        $importantExtensions = [
            'pdo', 'pdo_mysql', 'mysqli', 'mbstring', 'xml', 'json',
            'curl', 'gd', 'openssl', 'fileinfo', 'zip', 'bcmath',
            'redis', 'opcache', 'intl', 'sodium', 'gmp',
        ];

        foreach ($importantExtensions as $ext) {
            $result[$ext] = extension_loaded($ext)
                ? phpversion($ext)
                : 'غير محمل';
        }

        return $result;
    }

    /**
     * الحصول على مستوى تقرير الأخطاء كنص مقروء
     */
    private function getErrorReportingLevel(): string
    {
        $level = error_reporting();
        $levels = [
            E_ALL => 'E_ALL',
            E_ALL & ~E_NOTICE => 'E_ALL & ~E_NOTICE',
            E_ALL & ~E_DEPRECATED => 'E_ALL & ~E_DEPRECATED',
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
        ];

        return $levels[$level] ?? 'مستوى مخصص: ' . $level;
    }

    /**
     * تنسيق البايتات إلى وحدة قياس مقروءة
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

</div>
