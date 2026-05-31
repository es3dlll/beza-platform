# 12 - نظام الإشعارات — المصادقة الثنائية (2FA)

## TwoFactorActivated Notification

```php
<?php
// app/Notifications/TwoFactorActivated.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TwoFactorActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'fcm', 'mail'];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'تم تفعيل الحماية الإضافية',
            'body'  => 'تم تفعيل المصادقة الثنائية بنجاح. حسابك الآن أكثر أماناً.',
            'data'  => [
                'type' => '2fa_activated',
            ],
        ];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('تفعيل المصادقة الثنائية')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('تم تفعيل المصادقة الثنائية (2FA) لحسابك بنجاح.')
            ->line('إذا لم تقم أنت بهذا التغيير، يرجى التواصل مع الدعم فوراً.')
            ->action('إدارة الأمان', url('/settings/security'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'  => '2fa_activated',
            'title' => 'تم تفعيل المصادقة الثنائية',
            'body'  => 'تم تفعيل 2FA. حسابك الآن أكثر أماناً.',
        ];
    }
}
```

## TwoFactorDisabled Notification

```php
<?php
// app/Notifications/TwoFactorDisabled.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TwoFactorDisabled extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'تم تعطيل الحماية الإضافية',
            'body'  => 'تم تعطيل المصادقة الثنائية لحسابك',
            'data'  => [
                'type' => '2fa_disabled',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'  => '2fa_disabled',
            'title' => 'تم تعطيل المصادقة الثنائية',
            'body'  => 'تم تعطيل 2FA لحسابك',
        ];
    }
}
```
