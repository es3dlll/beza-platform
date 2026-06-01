# 12 - نظام الإشعارات (FCM + SMS + Email)

## ReferralCodeUsed Notification

```php
<?php
// app/Notifications/ReferralCodeUsed.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReferralCodeUsed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $referredName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('تم استخدام كود الإحالة الخاص بك')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("قام {$this->referredName} بالتسجيل باستخدام كود الإحالة الخاص بك")
            ->line('ستحصل على مكافأة 2 USD بعد أول معاملة للصديق');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'  => 'referral_code_used',
            'title' => 'تم استخدام كود الإحالة',
            'body'  => "قام {$this->referredName} بالتسجيل بكودك",
        ];
    }
}
```

## ReferralRewardReceived Notification

```php
<?php
// app/Notifications/ReferralRewardReceived.php

class ReferralRewardReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly float  $amount,
        private readonly string $type, // 'referrer' or 'referred'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $msg = $this->type === 'referrer'
            ? "حصلت على {$this->amount} USD مكافأة دعوة صديق"
            : "حصلت على {$this->amount} USD مكافأة تسجيل عبر دعوة";

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('مكافأة إحالة')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line($msg);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'  => 'referral_reward',
            'title' => 'مكافأة إحالة',
            'body'  => "حصلت على {$this->amount} USD",
        ];
    }
}
```
