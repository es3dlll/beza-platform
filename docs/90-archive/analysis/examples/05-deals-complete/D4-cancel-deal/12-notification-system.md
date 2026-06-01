# 12 - نظام الإشعارات (FCM + SMS + Email)

## RefundProcessed Notification

```php
<?php
// app/Notifications/RefundProcessed.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RefundProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $dealTitle,
        private readonly float  $amount,
        private readonly string $currency,
        private readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('استرجاع مبلغ صفقة ملغاة')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("تم إلغاء صفقة {$this->dealTitle}")
            ->line("السبب: {$this->reason}")
            ->line("تم استرجاع {$this->amount} {$this->currency} إلى محفظتك")
            ->action('عرض المحفظة', url('/wallet'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'  => 'refund_processed',
            'title' => 'استرجاع مبلغ',
            'body'  => "تم استرجاع {$this->amount} {$this->currency} من صفقة {$this->dealTitle}",
        ];
    }
}
```
