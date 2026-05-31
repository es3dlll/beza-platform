# 12 - نظام الإشعارات (FCM + SMS + Email)

## ProfitReceived Notification

```php
<?php
// app/Notifications/ProfitReceived.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProfitReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $dealTitle,
        private readonly float  $amount,
        private readonly string $currency,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('تم توزيع أرباح الصفقة')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("تم توزيع أرباح صفقة {$this->dealTitle}")
            ->line("حصلت على {$this->amount} {$this->currency} كأرباح")
            ->action('عرض المحفظة', url('/wallet'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'  => 'profit_received',
            'title' => 'أرباح جديدة',
            'body'  => "حصلت على {$this->amount} {$this->currency} أرباح من {$this->dealTitle}",
        ];
    }
}
```
