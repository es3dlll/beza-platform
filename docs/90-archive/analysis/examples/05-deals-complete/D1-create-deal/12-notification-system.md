# 12 - نظام الإشعارات (FCM + SMS + Email)

## DealCreatedNotification

```php
<?php
// app/Notifications/DealCreatedNotification.php

namespace App\Notifications;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Deal $deal,
        private readonly User $admin,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('صفقة جديدة: ' . $this->deal->title)
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("تم إنشاء صفقة جديدة بواسطة {$this->admin->name}")
            ->line("العنوان: {$this->deal->title}")
            ->line("رأس المال: {$this->deal->target_amount} {$this->deal->currency}")
            ->line("الربح المتوقع: {$this->deal->expected_profit_percentage}%")
            ->action('عرض الصفقة', url('/admin/deals/' . $this->deal->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'deal_created',
            'title'   => 'صفقة جديدة',
            'body'    => "تم إنشاء صفقة {$this->deal->title} بمبلغ {$this->deal->target_amount} {$this->deal->currency}",
            'deal_id' => $this->deal->id,
        ];
    }
}
```
