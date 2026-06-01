# 12 - إشعارات 2FA (Notification System)

## إشعار تفعيل 2FA

```php
<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TwoFactorEnabledNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم تفعيل المصادقة الثنائية - Beza')
            ->line('تم تفعيل المصادقة الثنائية لحسابك بنجاح.')
            ->line('إذا لم تقم بهذا الإجراء، يرجى التواصل مع الدعم الفوري.')
            ->action('مراجعة الإعدادات', url('/settings/security'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => '2fa_enabled',
            'title' => 'تم تفعيل المصادقة الثنائية',
            'message' => 'تم تفعيل المصادقة الثنائية لحسابك بنجاح',
        ];
    }
}
```

## إشعار محاولة 2FA فاشلة

```php
<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FailedTwoFactorAttempt extends Notification
{
    public function __construct(public string $ip, public string $userAgent) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('محاولة دخول مشبوهة - Beza')
            ->line('كانت هناك محاولة دخول إلى حسابك باستخدام رمز 2FA خاطئ.')
            ->line("IP: {$this->ip}")
            ->line("المتصفح: {$this->userAgent}")
            ->line('إذا كنت أنت، يرجى التأكد من إدخال الرمز الصحيح.')
            ->action('تأمين الحساب', url('/settings/security'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => '2fa_failed_attempt',
            'title' => 'محاولة دخول مشبوهة',
            'message' => 'محاولة دخول فاشلة باستخدام 2FA',
            'ip' => $this->ip,
        ];
    }
}
```

## إشعار استخدام رمز استرداد

```php
class RecoveryCodeUsedNotification extends Notification
{
    public function __construct(public int $remainingCodes) {}

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم استخدام رمز استرداد - Beza')
            ->line("تم استخدام أحد رموز الاسترداد الخاصة بك.")
            ->line("تبقى لديك {$this->remainingCodes} رموز استرداد.")
            ->action('إدارة رموز الاسترداد', url('/settings/security'));
    }
}
```
