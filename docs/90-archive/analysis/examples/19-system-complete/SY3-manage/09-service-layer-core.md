# 09 - طبقة الخدمات الأساسية: CacheManager, LogManager, QueueManager, MaintenanceManager (Core Service Layer)

<div dir="rtl">

## CacheManager

```php
<?php
// app/Services/System/CacheManager.php

namespace App\Services\System;

use Illuminate\Support\Facades\Artisan;

/**
 * مدير الذاكرة المؤقتة
 * مسؤول عن إدارة جميع أنواع الكاش في التطبيق
 */
class CacheManager
{
    /**
     * مسح جميع أنواع الذاكرة المؤقتة
     * 
     * @return array نتائج المسح لكل نوع
     */
    public function clear(): array
    {
        $results = [];

        // 1. مسح كاش التطبيق (Application Cache)
        // يشمل: cache driver مثل Redis أو file أو database
        $results['application'] = $this->runCommand('cache:clear');

        // 2. مسح كاش الإعدادات (Config Cache)
        // يحذف الملف المخزن للإعدادات المدمجة
        $results['config'] = $this->runCommand('config:clear');

        // 3. مسح كاش المسارات (Route Cache)
        // يحذف الملف المخزن للمسارات المدمجة
        $results['route'] = $this->runCommand('route:clear');

        // 4. مسح كاش القوالب (View Cache)
        // يحذف القوالب المترجمة (Blade cache)
        $results['view'] = $this->runCommand('view:clear');

        return $results;
    }

    /**
     * تحسين الكاش وتخزين الإعدادات والمسارات
     * لتحسين أداء التطبيق في بيئة الإنتاج
     * 
     * @return array نتائج التحسين
     */
    public function optimize(): array
    {
        $results = [];

        // 1. تخزين الإعدادات (Config Cache)
        // يدمج جميع ملفات config في ملف واحد للسرعة
        $results['config'] = $this->runCommand('config:cache');

        // 2. تخزين المسارات (Route Cache)
        // يدمج جميع المسارات في ملف واحد للسرعة
        $results['route'] = $this->runCommand('route:cache');

        return $results;
    }

    /**
     * تنفيذ أمر Artisan وإرجاع النتيجة
     * 
     * @param string $command اسم الأمر
     * @return array معلومات عن تنفيذ الأمر
     */
    private function runCommand(string $command): array
    {
        try {
            // بدء تخزين الإخراج
            Artisan::call($command);
            $output = Artisan::output();

            return [
                'success' => true,
                'command' => $command,
                'output'  => trim($output),
            ];
        } catch (\Exception $e) {
            // في حالة فشل الأمر، تسجيل الخطأ وإرجاعه
            report($e); // تسجيل الخطأ في سجل الأخطاء
            return [
                'success' => false,
                'command' => $command,
                'error'   => $e->getMessage(),
            ];
        }
    }
}
```

## LogManager

```php
<?php
// app/Services/System/LogManager.php

namespace App\Services\System;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * مدير السجلات
 * مسؤول عن قراءة ومسح وإدارة ملفات السجل
 */
class LogManager
{
    /**
     * مسار مجلد السجلات
     */
    private string $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs');
    }

    /**
     * عرض آخر 100 سطر من سجل Laravel الرئيسي
     * 
     * @return string محتوى آخر 100 سطر
     */
    public function view(): string
    {
        $logFile = $this->logPath . '/laravel.log';

        // التحقق من وجود ملف السجل
        if (!file_exists($logFile)) {
            return 'ملف السجل غير موجود.'; // ملف السجل غير موجود
        }

        // قراءة الملف بأكمله
        $content = file_get_contents($logFile);

        // تقسيم المحتوى إلى أسطر وأخذ آخر 100 سطر
        $lines = explode("\n", $content);
        $lastLines = array_slice($lines, -100);

        return implode("\n", $lastLines);
    }

    /**
     * مسح جميع ملفات السجل
     * يقوم بحذف جميع ملفات .log في مجلد السجلات
     * 
     * @return int عدد الملفات المحذوفة
     */
    public function clear(): int
    {
        $deletedCount = 0;

        // الحصول على جميع ملفات .log في مجلد السجلات
        $files = glob($this->logPath . '/*.log');

        foreach ($files as $file) {
            try {
                // حذف الملف
                if (unlink($file)) {
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                // تسجيل الخطأ والاستمرار مع الملف التالي
                report($e);
            }
        }

        return $deletedCount;
    }

    /**
     * عرض قائمة بملفات السجل مع الأحجام
     * 
     * @return array قائمة الملفات مع التفاصيل
     */
    public function list(): array
    {
        $files = [];
        $logFiles = glob($this->logPath . '/*.log');

        foreach ($logFiles as $file) {
            $files[] = [
                'name'       => basename($file),
                'path'       => $file,
                'size'       => filesize($file),
                'size_formatted' => $this->formatSize(filesize($file)),
                'modified'   => date('Y-m-d H:i:s', filemtime($file)),
                'created'    => date('Y-m-d H:i:s', filectime($file)),
            ];
        }

        // ترتيب الملفات حسب تاريخ التعديل (الأحدث أولاً)
        usort($files, fn($a, $b) => strtotime($b['modified']) - strtotime($a['modified']));

        return $files;
    }

    /**
     * عرض محتوى ملف سجل محدد
     * 
     * @param string $filename اسم الملف (بدون مسار)
     * @return array محتوى الملف مع معلومات
     */
    public function show(string $filename): array
    {
        $filePath = $this->logPath . '/' . $filename;

        // التحقق من وجود الملف
        if (!file_exists($filePath)) {
            throw new \RuntimeException("الملف {$filename} غير موجود");
        }

        // التحقق من إمكانية القراءة
        if (!is_readable($filePath)) {
            throw new \RuntimeException("لا يمكن قراءة الملف {$filename}");
        }

        // قراءة الملف (الحد الأقصى 1MB)
        $maxSize = 1024 * 1024; // 1 ميغابايت
        if (filesize($filePath) > $maxSize) {
            // إذا كان الملف كبيراً، نقرأ آخر 1000 سطر فقط
            $content = $this->tail($filePath, 1000);
        } else {
            $content = file_get_contents($filePath);
        }

        return [
            'name'       => $filename,
            'size'       => filesize($filePath),
            'size_formatted' => $this->formatSize(filesize($filePath)),
            'modified'   => date('Y-m-d H:i:s', filemtime($filePath)),
            'content'    => $content,
        ];
    }

    /**
     * قراءة آخر N سطر من ملف (tail)
     * 
     * @param string $filePath مسار الملف
     * @param int $lines عدد الأسطر
     * @return string آخر N سطر
     */
    private function tail(string $filePath, int $lines = 1000): string
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException("لا يمكن فتح الملف للقراءة");
        }

        $buffer = [];
        $position = 0;

        // قراءة الملف من النهاية إلى البداية
        fseek($handle, 0, SEEK_END);
        $fileSize = ftell($handle);

        for ($i = $fileSize - 1; $i >= 0 && count($buffer) <= $lines; $i--) {
            fseek($handle, $i);
            $char = fgetc($handle);

            if ($char === "\n" && $i !== $fileSize - 1) {
                $buffer[] = '';
            } else {
                $buffer[count($buffer) - 1] = $char . ($buffer[count($buffer) - 1] ?? '');
            }
        }

        fclose($handle);
        return implode("\n", array_reverse($buffer));
    }

    /**
     * تنسيق حجم الملف بطريقة مقروءة
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

## QueueManager

```php
<?php
// app/Services/System/QueueManager.php

namespace App\Services\System;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * مدير قائمة الانتظار
 * مسؤول عن مراقبة وإدارة عمال قائمة الانتظار
 */
class QueueManager
{
    /**
     * الحصول على حالة قائمة الانتظار
     * يعرض معلومات عن العمال والوظائف المعلقة والفاشلة
     * 
     * @return array حالة قائمة الانتظار
     */
    public function status(): array
    {
        $status = [
            'driver'       => config('queue.default'),
            'connection'   => config('queue.connections.' . config('queue.default')),
            'pending'      => 0,
            'failed'       => 0,
            'workers'      => [],
        ];

        // إذا كان driver قاعدة بيانات، نقرأ عدد الوظائف المعلقة
        if ($status['driver'] === 'database') {
            $status['pending'] = DB::table('jobs')->count();
        }

        // قراءة عدد الوظائف الفاشلة
        try {
            $status['failed'] = DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            $status['failed'] = 0;
        }

        // محاولة الحصول على معلومات العمال عبر Artisan
        try {
            Artisan::call('queue:status');
            $status['workers_info'] = Artisan::output();
        } catch (\Exception $e) {
            $status['workers_info'] = 'غير متاح';
        }

        return $status;
    }

    /**
     * إعادة تشغيل عمال قائمة الانتظار
     * يرسل إشارة لجميع العمال لإعادة التشغيل بعد إنهاء المهمة الحالية
     */
    public function restart(): void
    {
        try {
            Artisan::call('queue:restart');
        } catch (\Exception $e) {
            throw new \RuntimeException('فشل إعادة تشغيل عمال قائمة الانتظار: ' . $e->getMessage());
        }
    }

    /**
     * عرض المهام المجدولة
     * يعرض قائمة بجميع المهام المسجلة في جدولة المهام
     * 
     * @return array قائمة المهام المجدولة
     */
    public function scheduleList(): array
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
        $events = [];

        foreach ($schedule->events() as $event) {
            $expression = $event->expression;
            $command = $event->command ?? $event->description;

            // تحويل تعبير cron إلى نص مقروء
            $readable = $this->cronToReadable($expression);

            $events[] = [
                'command'      => $command,
                'expression'   => $expression,
                'readable'     => $readable,
                'timezone'     => $event->timezone ?? config('app.timezone'),
                'withoutOverlapping' => $event->withoutOverlapping,
                'onOneServer'  => $event->onOneServer,
                'environments' => $event->environments,
                'filters'      => $event->filters,
                'rejects'      => $event->rejects,
            ];
        }

        return $events;
    }

    /**
     * تحويل تعبير cron إلى نص مقروء
     * 
     * @param string $expression تعبير cron مثل "* * * * *"
     * @return string النص المقروء
     */
    private function cronToReadable(string $expression): string
    {
        $parts = explode(' ', $expression);

        $mapping = [
            0 => ['كل دقيقة', 'كل ساعة', 'كل يوم', 'كل شهر', 'كل يوم من الأسبوع'],
            1 => ['كل دقيقة', 'في الدقيقة %d', 'في الدقائق %s'],
            2 => ['كل ساعة', 'في الساعة %d', 'في الساعات %s'],
            3 => ['كل يوم', 'في اليوم %d', 'في الأيام %s'],
            4 => ['كل شهر', 'في الشهر %d', 'في الشهور %s'],
            5 => ['كل يوم من الأسبوع', 'في اليوم %d', 'في الأيام %s'],
        ];

        // تحليل بسيط للتعبير
        if ($expression === '* * * * *') {
            return 'كل دقيقة';
        }

        // تعبيرات شائعة
        $common = [
            '0 * * * *'   => 'كل ساعة',
            '0 0 * * *'   => 'كل يوم في منتصف الليل',
            '0 0 * * 0'   => 'كل أسبوع (الأحد)',
            '0 0 1 * *'   => 'أول يوم من كل شهر',
            '*/5 * * * *' => 'كل 5 دقائق',
            '*/10 * * * *' => 'كل 10 دقائق',
            '*/15 * * * *' => 'كل 15 دقيقة',
            '*/30 * * * *' => 'كل 30 دقيقة',
        ];

        return $common[$expression] ?? "تعبير cron: {$expression}";
    }
}
```

## MaintenanceManager

```php
<?php
// app/Services/System/MaintenanceManager.php

namespace App\Services\System;

use Illuminate\Support\Facades\Artisan;

/**
 * مدير وضع الصيانة
 * مسؤول عن تفعيل وتعطيل وضع الصيانة للتطبيق
 */
class MaintenanceManager
{
    /**
     * تبديل وضع الصيانة
     * 
     * @param bool $enabled true للتفعيل، false للتعطيل
     * @param string|null $message رسالة الصيانة (للمستخدمين)
     * @param int|null $retry دقائق إعادة المحاولة
     * @return array نتيجة العملية
     */
    public function toggle(bool $enabled, ?string $message = null, ?int $retry = null): array
    {
        if ($enabled) {
            return $this->enable($message, $retry);
        }

        return $this->disable();
    }

    /**
     * تفعيل وضع الصيانة
     * يستخدم الأمر php artisan down
     * 
     * @param string|null $message رسالة للمستخدمين
     * @param int|null $retry مدة إعادة المحاولة بالدقائق
     * @return array نتيجة التفعيل
     */
    private function enable(?string $message = null, ?int $retry = null): array
    {
        $params = [];

        // إضافة رسالة الصيانة إذا وجدت
        if ($message) {
            $params['--message'] = $message;
        }

        // إضافة وقت إعادة المحاولة إذا وجد
        if ($retry) {
            $params['--retry'] = $retry;
        }

        try {
            // تنفيذ أمر تفعيل الصيانة
            Artisan::call('down', $params);

            return [
                'success' => true,
                'message' => 'تم تفعيل وضع الصيانة بنجاح', // تم تفعيل وضع الصيانة
                'data'    => [
                    'maintenance_mode' => true,
                    'message'          => $message,
                    'retry'            => $retry,
                ],
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('فشل تفعيل وضع الصيانة: ' . $e->getMessage());
        }
    }

    /**
     * تعطيل وضع الصيانة
     * يستخدم الأمر php artisan up
     * 
     * @return array نتيجة التعطيل
     */
    private function disable(): array
    {
        try {
            // تنفيذ أمر تعطيل الصيانة
            Artisan::call('up');

            return [
                'success' => true,
                'message' => 'تم تعطيل وضع الصيانة بنجاح', // تم تعطيل وضع الصيانة
                'data'    => [
                    'maintenance_mode' => false,
                ],
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException('فشل تعطيل وضع الصيانة: ' . $e->getMessage());
        }
    }

    /**
     * التحقق من حالة وضع الصيانة
     * 
     * @return bool هل التطبيق في وضع الصيانة
     */
    public function isActive(): bool
    {
        // التحقق من وجود ملف وضع الصيانة
        return file_exists(storage_path('framework/down'));
    }
}
```

</div>
