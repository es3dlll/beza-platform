# 11 - الأحداث والمستمعون: CacheCleared, BackupCreated, MaintenanceModeChanged (Events and Listeners)

<div dir="rtl">

## نظرة عامة

نظام الأحداث في SY3-manage يتيح فصل منطق الإشعارات والتسجيل عن منطق العمليات. كل عملية مهمة تقوم بإطلاق حدث يمكن للمستمعين (Listeners) التقاطه للقيام بإجراءات إضافية مثل التسجيل أو الإشعار.

## الأحداث

### 1. حدث CacheCleared

```php
<?php
// app/Events/System/CacheCleared.php

namespace App\Events\System;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث مسح الكاش
 * يتم إطلاقه بعد مسح جميع أنواع الذاكرة المؤقتة بنجاح
 */
class CacheCleared
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * المشرف الذي قام بعملية المسح
     */
    public User $admin;

    /**
     * نتائج عملية المسح لكل نوع كاش
     * 
     * @var array<string, array>
     */
    public array $results;

    /**
     * الوقت الذي تم فيه المسح
     */
    public string $timestamp;

    /**
     * إنشاء حدث جديد
     * 
     * @param User $admin المشرف
     * @param array $results نتائج المسح
     */
    public function __construct(User $admin, array $results)
    {
        $this->admin     = $admin;
        $this->results   = $results;
        $this->timestamp = now()->toIso8601String();
    }
}
```

### 2. حدث BackupCreated

```php
<?php
// app/Events/System/BackupCreated.php

namespace App\Events\System;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث إنشاء نسخة احتياطية
 * يتم إطلاقه بعد إنشاء نسخة احتياطية جديدة بنجاح
 */
class BackupCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * اسم ملف النسخة الاحتياطية
     */
    public string $filename;

    /**
     * حجم الملف بالبايت
     */
    public int $size;

    /**
     * المشرف الذي قام بإنشاء النسخة
     */
    public User $admin;

    /**
     * وقت الإنشاء
     */
    public string $timestamp;

    /**
     * إنشاء حدث جديد
     * 
     * @param string $filename اسم الملف
     * @param int $size الحجم بالبايت
     * @param User $admin المشرف
     */
    public function __construct(string $filename, int $size, User $admin)
    {
        $this->filename  = $filename;
        $this->size      = $size;
        $this->admin     = $admin;
        $this->timestamp = now()->toIso8601String();
    }
}
```

### 3. حدث BackupFailed

```php
<?php
// app/Events/System/BackupFailed.php

namespace App\Events\System;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث فشل النسخة الاحتياطية
 * يتم إطلاقه عند فشل إنشاء نسخة احتياطية
 */
class BackupFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * المشرف الذي حاول إنشاء النسخة
     */
    public User $admin;

    /**
     * رسالة الخطأ
     */
    public string $error;

    /**
     * وقت الفشل
     */
    public string $timestamp;

    /**
     * إنشاء حدث جديد
     * 
     * @param User $admin المشرف
     * @param string $error رسالة الخطأ
     */
    public function __construct(User $admin, string $error)
    {
        $this->admin     = $admin;
        $this->error     = $error;
        $this->timestamp = now()->toIso8601String();
    }
}
```

### 4. حدث MaintenanceModeChanged

```php
<?php
// app/Events/System/MaintenanceModeChanged.php

namespace App\Events\System;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * حدث تغيير وضع الصيانة
 * يتم إطلاقه عند تفعيل أو تعطيل وضع الصيانة
 */
class MaintenanceModeChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * حالة الصيانة الجديدة
     * true = تفعيل، false = تعطيل
     */
    public bool $enabled;

    /**
     * المشرف الذي قام بالتغيير
     */
    public User $admin;

    /**
     * وقت التغيير
     */
    public string $timestamp;

    /**
     * إنشاء حدث جديد
     * 
     * @param bool $enabled حالة الصيانة
     * @param User $admin المشرف
     */
    public function __construct(bool $enabled, User $admin)
    {
        $this->enabled   = $enabled;
        $this->admin     = $admin;
        $this->timestamp = now()->toIso8601String();
    }
}
```

## المستمعون (Listeners)

### 1. مستمع تسجيل الإجراءات

```php
<?php
// app/Listeners/System/LogSystemOperation.php

namespace App\Listeners\System;

use App\Events\System\CacheCleared;
use App\Events\System\BackupCreated;
use App\Events\System\BackupFailed;
use App\Events\System\MaintenanceModeChanged;
use Illuminate\Support\Facades\Log;

/**
 * مستمع تسجيل عمليات النظام
 * يسجل جميع عمليات النظام المهمة في سجل التطبيق للتدقيق
 */
class LogSystemOperation
{
    /**
     * معالجة حدث مسح الكاش
     * 
     * @param CacheCleared $event
     */
    public function handleCacheCleared(CacheCleared $event): void
    {
        Log::channel('admin')->info('تم مسح الكاش بواسطة المشرف', [
            'admin'     => $event->admin->email,
            'admin_id'  => $event->admin->id,
            'results'   => $event->results,
            'timestamp' => $event->timestamp,
        ]);
    }

    /**
     * معالجة حدث إنشاء النسخة الاحتياطية
     * 
     * @param BackupCreated $event
     */
    public function handleBackupCreated(BackupCreated $event): void
    {
        Log::channel('admin')->info('تم إنشاء نسخة احتياطية جديدة', [
            'admin'     => $event->admin->email,
            'admin_id'  => $event->admin->id,
            'filename'  => $event->filename,
            'size'      => $event->size,
            'timestamp' => $event->timestamp,
        ]);
    }

    /**
     * معالجة حدث فشل النسخة الاحتياطية
     * 
     * @param BackupFailed $event
     */
    public function handleBackupFailed(BackupFailed $event): void
    {
        Log::channel('admin')->error('فشل إنشاء نسخة احتياطية', [
            'admin'     => $event->admin->email,
            'admin_id'  => $event->admin->id,
            'error'     => $event->error,
            'timestamp' => $event->timestamp,
        ]);
    }

    /**
     * معالجة حدث تغيير وضع الصيانة
     * 
     * @param MaintenanceModeChanged $event
     */
    public function handleMaintenanceModeChanged(MaintenanceModeChanged $event): void
    {
        $action = $event->enabled ? 'تفعيل' : 'تعطيل';

        Log::channel('admin')->warning("تم {$action} وضع الصيانة", [
            'admin'     => $event->admin->email,
            'admin_id'  => $event->admin->id,
            'mode'      => $event->enabled ? 'down' : 'up',
            'timestamp' => $event->timestamp,
        ]);
    }
}
```

### 2. مستمع الإشعارات

```php
<?php
// app/Listeners/System/NotifyAdminAboutBackup.php

namespace App\Listeners\System;

use App\Events\System\BackupCreated;
use App\Events\System\BackupFailed;
use App\Notifications\System\BackupCompletedNotification;
use App\Notifications\System\BackupFailedNotification;

/**
 * مستمع إشعارات النسخ الاحتياطي
 * يقوم بإرسال إشعارات للمشرفين عند اكتمال أو فشل النسخ الاحتياطي
 */
class NotifyAdminAboutBackup
{
    /**
     * معالجة حدث اكتمال النسخة الاحتياطية
     * 
     * @param BackupCreated $event
     */
    public function handleBackupCreated(BackupCreated $event): void
    {
        // إرسال إشعار للمشرف الذي قام بالعملية
        $event->admin->notify(new BackupCompletedNotification(
            filename: $event->filename,
            size: $event->size,
            timestamp: $event->timestamp
        ));

        // يمكن أيضاً إرسال إشعار لجميع المشرفين
        // User::where('role', 'admin')->each->notify(...);
    }

    /**
     * معالجة حدث فشل النسخة الاحتياطية
     * 
     * @param BackupFailed $event
     */
    public function handleBackupFailed(BackupFailed $event): void
    {
        // إرسال إشعار فوري للمشرف بالفشل
        $event->admin->notify(new BackupFailedNotification(
            error: $event->error,
            timestamp: $event->timestamp
        ));
    }
}
```

### 3. مستمع التنظيف

```php
<?php
// app/Listeners/System/CleanOldBackups.php

namespace App\Listeners\System;

use App\Events\System\BackupCreated;
use Illuminate\Support\Facades\Storage;

/**
 * مستمع تنظيف النسخ الاحتياطية القديمة
 * يقوم بحذف النسخ الاحتياطية التي يتجاوز عمرها الحد المسموح به
 */
class CleanOldBackups
{
    /**
     * الحد الأقصى لعدد النسخ الاحتياطية للاحتفاظ بها
     */
    private int $maxBackups = 30;

    /**
     * عدد أيام الاحتفاظ بالنسخ الاحتياطية
     */
    private int $maxAgeDays = 90;

    /**
     * معالجة حدث إنشاء نسخة احتياطية جديدة
     * يقوم بتنظيف النسخ القديمة
     * 
     * @param BackupCreated $event
     */
    public function handleBackupCreated(BackupCreated $event): void
    {
        $backupPath = storage_path('app/backups');
        $files = glob($backupPath . '/backup_*.sql.gz');

        // ترتيب الملفات حسب تاريخ الإنشاء (الأقدم أولاً)
        usort($files, fn($a, $b) => filectime($a) - filectime($b));

        // 1. حذف النسخ التي تجاوزت الحد الأقصى للعدد
        if (count($files) > $this->maxBackups) {
            $toDelete = array_slice($files, 0, count($files) - $this->maxBackups);
            foreach ($toDelete as $file) {
                unlink($file);
                \Illuminate\Support\Facades\Log::info('تم حذف نسخة احتياطية قديمة (تجاوز الحد الأقصى للعدد)', [
                    'file' => basename($file),
                ]);
            }
        }

        // 2. حذف النسخ التي تجاوزت الحد الأقصى للعمر
        $cutoff = now()->subDays($this->maxAgeDays)->timestamp;
        foreach ($files as $file) {
            if (filectime($file) < $cutoff) {
                unlink($file);
                \Illuminate\Support\Facades\Log::info('تم حذف نسخة احتياطية قديمة (تجاوز الحد الأقصى للعمر)', [
                    'file' => basename($file),
                ]);
            }
        }
    }
}
```

## تسجيل الأحداث والمستمعين

```php
<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * ربط الأحداث بالمستمعين
     * 
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // حدث مسح الكاش → تسجيل العملية
        \App\Events\System\CacheCleared::class => [
            \App\Listeners\System\LogSystemOperation::class . '@handleCacheCleared',
        ],

        // حدث إنشاء نسخة احتياطية → تسجيل + إشعار + تنظيف
        \App\Events\System\BackupCreated::class => [
            \App\Listeners\System\LogSystemOperation::class . '@handleBackupCreated',
            \App\Listeners\System\NotifyAdminAboutBackup::class . '@handleBackupCreated',
            \App\Listeners\System\CleanOldBackups::class . '@handleBackupCreated',
        ],

        // حدث فشل نسخة احتياطية → تسجيل + إشعار
        \App\Events\System\BackupFailed::class => [
            \App\Listeners\System\LogSystemOperation::class . '@handleBackupFailed',
            \App\Listeners\System\NotifyAdminAboutBackup::class . '@handleBackupFailed',
        ],

        // حدث تغيير وضع الصيانة → تسجيل
        \App\Events\System\MaintenanceModeChanged::class => [
            \App\Listeners\System\LogSystemOperation::class . '@handleMaintenanceModeChanged',
        ],
    ];

    /**
     * تسجيل أي أحداث إضافية
     */
    public function boot(): void
    {
        parent::boot();
    }
}
```

## مخطط تفاعل الأحداث

```
CacheManager::clear()
    ↓
إطلاق: CacheCleared($admin, $results)
    ↓
    ├── LogSystemOperation::handleCacheCleared()
    │   └── Log::channel('admin')->info('تم مسح الكاش...')
    │
    └── [مستمعون مستقبليون]


BackupManager::create()
    ↓
    ├── نجاح → إطلاق: BackupCreated($filename, $size, $admin)
    │   ├── LogSystemOperation::handleBackupCreated()
    │   ├── NotifyAdminAboutBackup::handleBackupCreated()
    │   └── CleanOldBackups::handleBackupCreated()
    │
    └── فشل → إطلاق: BackupFailed($admin, $error)
        ├── LogSystemOperation::handleBackupFailed()
        └── NotifyAdminAboutBackup::handleBackupFailed()


MaintenanceManager::toggle()
    ↓
إطلاق: MaintenanceModeChanged($enabled, $admin)
    ↓
    └── LogSystemOperation::handleMaintenanceModeChanged()
```

</div>
