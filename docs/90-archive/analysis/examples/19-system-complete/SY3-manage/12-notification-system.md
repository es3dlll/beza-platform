# 12 - نظام الإشعارات: إخطار المشرف عند اكتمال/فشل النسخة الاحتياطية (Notification System)

<div dir="rtl">

## نظرة عامة

نظام الإشعارات في SY3-manage مسؤول عن إخطار المشرفين بنتائج العمليات غير المتزامنة. حالياً، يستخدم بشكل أساسي لإشعارات النسخ الاحتياطي لأنها قد تستغرق وقتاً طويلاً.

## كلاسات الإشعارات

### 1. إشعار اكتمال النسخة الاحتياطية

```php
<?php
// app/Notifications/System/BackupCompletedNotification.php

namespace App\Notifications\System;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * إشعار اكتمال النسخة الاحتياطية
 * يتم إرساله للمشرف عند إنشاء نسخة احتياطية جديدة بنجاح
 */
class BackupCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param string $filename اسم ملف النسخة الاحتياطية
     * @param int $size حجم الملف بالبايت
     * @param string $timestamp وقت الإنشاء
     */
    public function __construct(
        public string $filename,
        public int    $size,
        public string $timestamp,
    ) {}

    /**
     * تحديد قنوات الإشعار
     * يمكن إرسال الإشعار عبر قنوات متعددة
     * 
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // قنوات الإشعار: قاعدة البيانات، البريد الإلكتروني
        $channels = ['database'];

        // إذا كان المستخدم يفضل البريد الإلكتروني، نضيفه
        if ($notifiable->prefers_email_notifications ?? false) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * تمثيل الإشعار كصفيف لتخزينه في قاعدة البيانات
     * 
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $sizeFormatted = $this->formatSize($this->size);

        return [
            'title'      => 'اكتمال النسخة الاحتياطية', // عنوان الإشعار
            'body'       => "تم إنشاء نسخة احتياطية جديدة: {$this->filename} ({$sizeFormatted})",
            'icon'       => 'database', // أيقونة الإشعار
            'type'       => 'success', // نوع الإشعار (نجاح)
            'filename'   => $this->filename,
            'size'       => $this->size,
            'action_url' => url('/admin/system/backup/list'), // رابط لعرض النسخ
            'timestamp'  => $this->timestamp,
        ];
    }

    /**
     * تمثيل الإشعار كبريد إلكتروني
     * 
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        $sizeFormatted = $this->formatSize($this->size);

        return (new MailMessage)
            ->subject('✅ اكتمال النسخة الاحتياطية - ' . config('app.name'))
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('تم إنشاء نسخة احتياطية جديدة لقاعدة البيانات بنجاح.')
            ->line("اسم الملف: {$this->filename}")
            ->line("الحجم: {$sizeFormatted}")
            ->line("التاريخ: {$this->timestamp}")
            ->action('عرض النسخ الاحتياطية', url('/admin/system/backup/list'))
            ->line('شكراً لاستخدامك نظام إدارة النظام.');
    }

    /**
     * تنسيق حجم الملف
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

### 2. إشعار فشل النسخة الاحتياطية

```php
<?php
// app/Notifications/System/BackupFailedNotification.php

namespace App\Notifications\System;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * إشعار فشل النسخة الاحتياطية
 * يتم إرساله للمشرف عند فشل إنشاء نسخة احتياطية
 */
class BackupFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param string $error رسالة الخطأ
     * @param string $timestamp وقت الفشل
     */
    public function __construct(
        public string $error,
        public string $timestamp,
    ) {}

    /**
     * تحديد قنوات الإشعار
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->prefers_email_notifications ?? false) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * تمثيل الإشعار في قاعدة البيانات
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'      => 'فشل النسخة الاحتياطية', // عنوان الإشعار
            'body'       => "فشل إنشاء النسخة الاحتياطية. الخطأ: {$this->error}",
            'icon'       => 'alert-triangle', // أيقونة تحذير
            'type'       => 'error', // نوع الإشعار (خطأ)
            'error'      => $this->error,
            'action_url' => url('/admin/system/backup'), // رابط للمحاولة مرة أخرى
            'timestamp'  => $this->timestamp,
        ];
    }

    /**
     * تمثيل الإشعار كبريد إلكتروني
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ فشل النسخة الاحتياطية - ' . config('app.name'))
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('للأسف، فشلت عملية إنشاء النسخة الاحتياطية لقاعدة البيانات.')
            ->line("تفاصيل الخطأ: {$this->error}")
            ->line("التاريخ: {$this->timestamp}")
            ->action('المحاولة مرة أخرى', url('/admin/system/backup'))
            ->line('يرجى التحقق من سجلات النظام لمزيد من التفاصيل.');
    }
}
```

### 3. إشعار تغيير وضع الصيانة

```php
<?php
// app/Notifications/System/MaintenanceModeNotification.php

namespace App\Notifications\System;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * إشعار تغيير وضع الصيانة
 * يتم إرساله لجميع المشرفين عند تفعيل أو تعطيل وضع الصيانة
 */
class MaintenanceModeNotification extends Notification
{
    use Queueable;

    /**
     * @param bool $enabled true للتفعيل، false للتعطيل
     * @param string $adminName اسم المشرف الذي قام بالتغيير
     * @param string $timestamp وقت التغيير
     */
    public function __construct(
        public bool   $enabled,
        public string $adminName,
        public string $timestamp,
    ) {}

    /**
     * قنوات الإشعار
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * تمثيل الإشعار في قاعدة البيانات
     */
    public function toArray(object $notifiable): array
    {
        $action = $this->enabled ? 'تفعيل' : 'تعطيل';
        $type   = $this->enabled ? 'warning' : 'info';

        return [
            'title'      => "{$action} وضع الصيانة",
            'body'       => "قام {$this->adminName} بـ {$action} وضع الصيانة.",
            'icon'       => $this->enabled ? 'shield-off' : 'shield-check',
            'type'       => $type,
            'action_by'  => $this->adminName,
            'action_url' => url('/admin/system/maintenance'),
            'timestamp'  => $this->timestamp,
        ];
    }
}
```

## جدول الإشعارات (notifications)

لاحظ أن SY3-manage تستخدم جدول `notifications` المدمج في Laravel:

```sql
-- جدول الإشعارات (موجود مسبقاً في Laravel)
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,           -- UUID
    type VARCHAR(255) NOT NULL,         -- كلاس الإشعار
    notifiable_type VARCHAR(255) NOT NULL, -- نوع الكيان (App\Models\User)
    notifiable_id BIGINT UNSIGNED NOT NULL, -- معرف الكيان
    data TEXT NOT NULL,                 -- بيانات الإشعار (JSON)
    read_at TIMESTAMP NULL,             -- وقت القراءة
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX notifiable_type_index (notifiable_type, notifiable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## إرسال الإشعار

```php
// في المستمع NotifyAdminAboutBackup
$event->admin->notify(new BackupCompletedNotification(
    filename: $event->filename,
    size: $event->size,
    timestamp: $event->timestamp
));

// إرسال لجميع المشرفين
use App\Models\User;
User::where('role', 'admin')
    ->each(fn($admin) => $admin->notify(new MaintenanceModeNotification(
        enabled: true,
        adminName: auth()->user()->name,
        timestamp: now()->toIso8601String()
    )));
```

## قراءة الإشعارات عبر API

```php
// routes/api.php
Route::middleware(['auth:api'])->prefix('notifications')->group(function () {
    Route::get('/', function () {
        // إرجاع الإشعارات غير المقروءة
        return auth()->user()->unreadNotifications;
    });

    Route::post('/{id}/read', function ($id) {
        // تعيين إشعار كمقروء
        auth()->user()->notifications()->findOrFail($id)->markAsRead();
        return response()->json(['success' => true]);
    });

    Route::post('/read-all', function () {
        // تعيين جميع الإشعارات كمقروءة
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    });
});
```

## ملخص الإشعارات

| الإشعار | متى يرسل | القنوات | الجمهور |
|---------|---------|---------|---------|
| BackupCompleted | بعد نجاح النسخة الاحتياطية | Database, Email | المشرف الذي طلب العملية |
| BackupFailed | بعد فشل النسخة الاحتياطية | Database, Email | المشرف الذي طلب العملية |
| MaintenanceMode | بعد تغيير وضع الصيانة | Database | جميع المشرفين |

</div>
