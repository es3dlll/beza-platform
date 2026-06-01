# 12 - نظام الإشعارات (FCM + SMS + Email)

## إشعار تأكيد الصرافة

```php
<?php
// app/Notifications/ExchangeConfirmed.php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExchangeConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Transaction $transaction,
        private readonly string      $fromCurrency,
        private readonly string      $toCurrency,
        private readonly float       $convertedAmount,
        private readonly float       $rate,
        private readonly float       $fee,
        private readonly float       $feePercentage,
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
            ->subject('تمت عملية الصرافة بنجاح')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("تم تحويل {$this->transaction->amount} {$this->fromCurrency} → {$this->convertedAmount} {$this->toCurrency}")
            ->line("سعر الصرف: 1 {$this->toCurrency} = {$this->rate} {$this->fromCurrency}")
            ->line("الرسوم: {$this->fee} {$this->fromCurrency} ({$this->feePercentage}%)")
            ->line("رقم المرجع: {$this->transaction->reference_number}")
            ->action('عرض التفاصيل', url('/transactions/' . $this->transaction->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'exchange_confirmed',
            'title'            => 'تمت الصرافة',
            'body'             => "تحويل {$this->transaction->amount} {$this->fromCurrency} → {$this->convertedAmount} {$this->toCurrency}",
            'transaction_id'   => $this->transaction->id,
            'reference_number' => $this->transaction->reference_number,
            'from_currency'    => $this->fromCurrency,
            'to_currency'      => $this->toCurrency,
            'amount'           => (float) $this->transaction->amount,
            'converted_amount' => $this->convertedAmount,
            'rate'             => $this->rate,
            'fee'              => $this->fee,
        ];
    }
}
```

## FCM Channel (مخصص)

```php
<?php
// app/Notifications/Channels/FcmChannel.php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class FcmChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!$notifiable->fcm_token) {
            return;
        }

        $data = $notification->toArray($notifiable);

        Http::withToken(config('services.fcm.server_key'))
            ->post('https://fcm.googleapis.com/fcm/send', [
                'to'           => $notifiable->fcm_token,
                'notification' => [
                    'title' => $data['title'],
                    'body'  => $data['body'],
                    'sound' => 'default',
                ],
                'data' => $data,
                'priority' => 'high',
            ]);
    }
}
```

## محتوى الإشعارات

| الإشعار | المحتوى | القناة |
|---------|---------|--------|
| صرافة ناجحة | "تم تحويل 100,000 SYP → 7.69 USD بسعر 13,000" | FCM + Email + DB |
| تفاصيل الصرافة | "الرسوم: 1,500 SYP (1.5%)، رقم المرجع: BZ..." | FCM + DB |

## أمثلة SMS (للطوارئ)

```php
public function toSms(object $notifiable): string
{
    return "صرافة Beza: {$this->transaction->amount} {$this->fromCurrency} → {$this->convertedAmount} {$this->toCurrency}. رقم المرجع: {$this->transaction->reference_number}";
}
```
