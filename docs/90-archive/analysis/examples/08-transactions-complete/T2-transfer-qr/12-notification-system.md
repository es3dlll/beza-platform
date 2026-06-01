# 12 - نظام الإشعارات (FCM + SMS + Email)

## Notification

```php
<?php
// app/Notifications/QRTransferNotification.php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\ContractsQueueShouldQueue;
use Illuminate\Notifications\MessagesMailMessage;
use Illuminate\NotificationsNotification;

class QRTransferNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Transaction $transaction,
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
        return (new MailMessage)
            ->subject('تمت العملية بنجاح')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("تم تنفيذ العملية بنجاح")
            ->line("رقم المرجع: {$this->transaction->reference_number}")
            ->action('عرض التفاصيل', url('/transactions/' . $this->transaction->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'qr_payment',
            'title'            => 'التحويل عبر QR',
            'body'             => "تمت العملية بنجاح",
            'transaction_id'   => $this->transaction->id,
            'reference_number' => $this->transaction->reference_number,
            'amount'           => (float) $this->transaction->amount,
        ];
    }
}
```
