# 12 - نظام الإشعارات (Notification System)

## SettingsChangedNotification

```php
<?php
// app/Notifications/Admin/SettingsChangedNotification.php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SettingsChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly array $changes,
        private readonly int   $changedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summary = collect($this->changes)
            ->map(fn($v, $k) => "{$k}: {$v}")
            ->implode("\n");

        return (new MailMessage)
            ->subject('⚙️ تم تغيير إعدادات المنصة')
            ->greeting('مرحباً أيها المشرف')
            ->line("قام المشرف #{$this->changedBy} بتحديث الإعدادات:")
            ->line($summary)
            ->action('عرض الإعدادات', url('/admin/settings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'settings_changed',
            'title'      => '⚙️ تغيير الإعدادات',
            'body'       => 'قام أحد المشرفين بتعديل إعدادات المنصة',
            'changes'    => $this->changes,
            'changed_by' => $this->changedBy,
        ];
    }
}
```
