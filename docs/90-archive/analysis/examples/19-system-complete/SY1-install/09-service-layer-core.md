# 09 - سيرفس لير — RequirementChecker (فحص المتطلبات)

```php
<?php
// app/Services/Install/RequirementChecker.php

namespace App\Services\Install;

use Illuminate\Support\Facades\Log;

class RequirementChecker
{
    /**
     * قائمة إضافات PHP المطلوبة لتشغيل Beza
     */
    private const REQUIRED_EXTENSIONS = [
        'BCMath'   => 'مطلوب للعمليات الحسابية الدقيقة',
        'Ctype'    => 'مطلوب للتحقق من الأنواع في Laravel',
        'JSON'     => 'مطلوب لمعالجة الـ JSON',
        'Mbstring' => 'مطلوب للتعامل مع النصوص متعددة البايت',
        'OpenSSL'  => 'مطلوب للتشفير والاتصالات الآمنة',
        'PDO'      => 'مطلوب للاتصال بقواعد البيانات',
        'Tokenizer'  => 'مطلوب لمحلل Laravel',
        'XML'        => 'مطلوب لمعالجة XML',
        'cURL'       => 'مطلوب لطلبات HTTP الخارجية',
        'GD'         => 'مطلوب لمعالجة الصور',
        'Redis'      => 'مطلوب للتخزين المؤقت والجلسات',
        'ZIP'        => 'مطلوب لضغط الملفات',
    ];

    /**
     * قائمة الأوامر المطلوب توفرها في الخادم
     */
    private const REQUIRED_COMMANDS = [
        'php'      => 'php version',
        'composer' => 'Composer لإدارة الحزم',
        'mysql'    => 'MySQL client',
        'redis-cli' => 'Redis client',
        'git'      => 'Git للتحكم بالإصدارات',
    ];

    /**
     * فحص جميع المتطلبات دفعة واحدة
     *
     * @return array<string, array{pass: bool, message: string}>
     */
    public function checkAll(): array
    {
        $results = [];

        // فحص إصدار PHP
        $results['php_version'] = $this->checkPhpVersion();

        // فحص إضافات PHP
        foreach (self::REQUIRED_EXTENSIONS as $ext => $description) {
            $results['ext_' . strtolower($ext)] = $this->checkExtension($ext, $description);
        }

        // فحص الأوامر
        foreach (self::REQUIRED_COMMANDS as $cmd => $description) {
            $results['cmd_' . $cmd] = $this->checkCommand($cmd, $description);
        }

        // فحص صلاحيات الملفات
        $results['perm_storage']    = $this->checkDirectoryPermission(storage_path());
        $results['perm_bootstrap']  = $this->checkDirectoryPermission(base_path('bootstrap/cache'));
        $results['perm_env']        = $this->checkFilePermission(base_path('.env'));

        // فحص الذاكرة
        $results['memory_limit']    = $this->checkMemoryLimit();

        return $results;
    }

    /**
     * فحص إصدار PHP — يجب أن يكون 8.1 أو أحدث
     */
    private function checkPhpVersion(): array
    {
        $version = PHP_VERSION;
        $pass    = version_compare($version, '8.1.0', '>=');

        return [
            'pass'    => $pass,
            'message' => $pass
                ? "إصدار PHP {$version} — متوافق"
                : "إصدار PHP {$version} — غير متوافق. مطلوب PHP 8.1 أو أحدث",
            'value'   => $version,
        ];
    }

    /**
     * فحص إضافة PHP محددة
     */
    private function checkExtension(string $extension, string $description): array
    {
        $loaded = extension_loaded($extension);

        return [
            'pass'    => $loaded,
            'message' => $loaded
                ? "الإضافة {$extension} — مثبتة"
                : "الإضافة {$extension} — غير مثبتة ({$description})",
            'extension' => $extension,
        ];
    }

    /**
     * فحص وجود أمر في النظام
     */
    private function checkCommand(string $command, string $description): array
    {
        $output = null;
        $resultCode = null;

        // تنفيذ which أو where حسب النظام
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("where {$command} 2>NUL", $output, $resultCode);
        } else {
            exec("which {$command} 2>/dev/null", $output, $resultCode);
        }

        $found = $resultCode === 0;

        return [
            'pass'    => $found,
            'message' => $found
                ? "الأمر {$command} — موجود ({$output[0] ?? 'غير معروف'})"
                : "الأمر {$command} — غير موجود ({$description})",
            'command' => $command,
        ];
    }

    /**
     * فحص صلاحيات القراءة والكتابة لمجلد
     */
    private function checkDirectoryPermission(string $path): array
    {
        $exists   = is_dir($path);
        $readable = $exists && is_readable($path);
        $writable = $exists && is_writable($path);
        $pass     = $exists && $readable && $writable;

        return [
            'pass'    => $pass,
            'message' => $pass
                ? "المجلد {$path} — قابل للقراءة والكتابة"
                : "المجلد {$path} — مشكلة في الصلاحيات (موجود: " . ($exists ? 'نعم' : 'لا') . ", قراءة: " . ($readable ? 'نعم' : 'لا') . ", كتابة: " . ($writable ? 'نعم' : 'لا') . ")",
            'path' => $path,
        ];
    }

    /**
     * فحص صلاحيات القراءة والكتابة لملف
     */
    private function checkFilePermission(string $path): array
    {
        $exists   = file_exists($path);
        $readable = $exists && is_readable($path);
        $writable = $exists && is_writable($path);
        $pass     = $exists && $readable && $writable;

        return [
            'pass'    => $pass,
            'message' => $pass
                ? "الملف {$path} — قابل للقراءة والكتابة"
                : "الملف {$path} — مشكلة في الصلاحيات",
            'path' => $path,
        ];
    }

    /**
     * فحص حد الذاكرة المسموح به
     */
    private function checkMemoryLimit(): array
    {
        $memoryLimit = ini_get('memory_limit');
        $pass = $memoryLimit !== '-1' && $this->convertToBytes($memoryLimit) >= 128 * 1024 * 1024;

        return [
            'pass'    => $pass,
            'message' => $pass
                ? "الذاكرة المسموحة: {$memoryLimit} — كافية"
                : "الذاكرة المسموحة: {$memoryLimit} — قد تكون غير كافية (المطلوب 128M على الأقل)",
            'value' => $memoryLimit,
        ];
    }

    /**
     * تحويل قيمة الذاكرة النصية إلى بايت
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last  = strtolower($value[strlen($value) - 1]);
        $num   = (int) substr($value, 0, -1);

        return match ($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => (int) $value,
        };
    }
}
```
