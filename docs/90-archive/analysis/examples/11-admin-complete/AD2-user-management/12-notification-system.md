# 12 - نظام الإشعارات (Notification System)

## UserAccountStatusChanged Notification

```php
<?php
// app/Notifications/UserAccountStatusChanged.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserAccountStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $status,
        private readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
        }
        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->status) {
            'suspended' => 'تم تعليق حسابك في Beza',
            'blocked'   => 'تم حظر حسابك في Beza',
            'active'    => 'تم تفعيل حسابك في Beza',
            default     => 'تحديث حالة حساب Beza',
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('مرحباً ' . $notifiable->name)
            ->line($this->message)
            ->action('الدعم الفني', url('/support'));
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => match ($this->status) {
                'suspended' => '⚠️ حسابك معلق',
                'blocked'   => '🚫 تم حظر حسابك',
                'active'    => '✅ تم تفعيل حسابك',
            },
            'body' => $this->message,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'account_status',
            'status'  => $this->status,
            'message' => $this->message,
            'title'   => match ($this->status) {
                'suspended' => 'تعليق حساب',
                'blocked'   => 'حظر حساب',
                'active'    => 'تفعيل حساب',
            },
        ];
    }
}
```

## إشعار للمشرف عند تغيير الحالة

```php
<?php
// app/Notifications/Admin/UserStatusChangedAlert.php

namespace App\Notifications\Admin;

use Illuminate\Notifications\Notification;

class UserStatusChangedAlert extends Notification
{
    public function __construct(
        private readonly int    $targetUserId,
        private readonly string $targetName,
        private readonly string $newStatus,
        private readonly int    $changedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'user_status_changed',
            'title'       => 'تغيير حالة مستخدم',
            'body'        => "تم تغيير حالة {$this->targetName} إلى {$this->newStatus}",
            'user_id'     => $this->targetUserId,
            'new_status'  => $this->newStatus,
            'changed_by'  => $this->changedBy,
        ];
    }
}
```
