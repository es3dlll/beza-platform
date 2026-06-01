# 12 - نظام الإشعارات (Notification System)

## DisputeNotification

```php
<?php
// app/Notifications/DisputeNotification.php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string  $type,
        private readonly Dispute $dispute,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->type === 'opened') {
            return (new MailMessage)
                ->subject('📝 تم فتح نزاع')
                ->line("رقم النزاع: #{$this->dispute->id}")
                ->line("السبب: {$this->dispute->reason}")
                ->line("سيتم مراجعة النزاع خلال 48 ساعة");
        }

        $resolution = match ($this->dispute->resolution) {
            'refund'         => '✅ تم استرجاع المبلغ',
            'partial_refund' => '✅ تم استرجاع جزء من المبلغ',
            'reject'         => '❌ تم رفض النزاع',
            default          => 'تم حل النزاع',
        };

        return (new MailMessage)
            ->subject("⚖️ نتيجة النزاع #{$this->dispute->id}")
            ->greeting('مرحباً')
            ->line($resolution)
            ->line($this->dispute->admin_notes ?? '')
            ->action('عرض التفاصيل', url('/support/disputes/' . $this->dispute->id));
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->type === 'opened'
                ? '📝 تم فتح نزاع جديد'
                : "⚖️ تم حل النزاع #{$this->dispute->id}",
            'body' => $this->type === 'opened'
                ? "نزاعك برقم {$this->dispute->id} قيد المراجعة"
                : "نتيجة النزاع: {$this->dispute->resolution}",
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => "dispute_{$this->type}",
            'dispute_id'   => $this->dispute->id,
            'resolution'   => $this->dispute->resolution,
            'title'        => "نزاع #{$this->dispute->id}",
            'body'         => $this->dispute->reason,
        ];
    }
}
```
