# 12 - نظام الإشعارات (FCM + SMS + Email)

## InvestmentConfirmed Notification

```php
<?php
// app/Notifications/InvestmentConfirmed.php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\DealInvestment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvestmentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly DealInvestment $investment,
        private readonly Deal          $deal,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('تم تأكيد استثمارك')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("تم استثمار {$this->investment->amount} {$this->investment->currency} في صفقة {$this->deal->title}")
            ->line("نسبة الربح المتوقعة: {$this->deal->expected_profit_percentage}%")
            ->action('عرض التفاصيل', url('/investments/' . $this->investment->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'investment_confirmed',
            'title'         => 'تم تأكيد الاستثمار',
            'body'          => "استثمار {$this->investment->amount} {$this->investment->currency} في {$this->deal->title}",
            'investment_id' => $this->investment->id,
            'deal_id'       => $this->deal->id,
        ];
    }
}
```
