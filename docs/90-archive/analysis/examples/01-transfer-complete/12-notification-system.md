# 12 - نظام الإشعارات (FCM + SMS + Email)

## وتنبيه المستخدمين (User Notification)

### 1. إشعار إرسال المال (للمرسل)

```php
<?php
// app/Notifications/TransactionSent.php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Transaction $transaction,
        private readonly string      $receiverName,
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
        $currency = $this->transaction->fromWallet?->currency ?? 'USD';

        return (new MailMessage)
            ->subject('تم تحويل المبلغ بنجاح')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("تم تحويل {$this->transaction->amount} {$currency} إلى {$this->receiverName}")
            ->line("رقم المرجع: {$this->transaction->reference_number}")
            ->line("الرصيد المتبقي: {$this->transaction->fromWallet?->balance} {$currency}")
            ->action('عرض التفاصيل', url('/transactions/' . $this->transaction->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'transaction_sent',
            'title'            => 'تم التحويل',
            'body'             => "تحويل {$this->transaction->amount} إلى {$this->receiverName}",
            'transaction_id'   => $this->transaction->id,
            'reference_number' => $this->transaction->reference_number,
            'amount'           => (float) $this->transaction->amount,
        ];
    }
}
```

### 2. إشعار استلام المال (للمستلم)

```php
<?php
// app/Notifications/TransactionReceived.php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Transaction $transaction,
        private readonly string      $senderName,
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
        $currency = $this->transaction->toWallet?->currency ?? 'USD';

        return (new MailMessage)
            ->subject('تم استلام مبلغ جديد')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("تم استلام {$this->transaction->amount} {$currency} من {$this->senderName}")
            ->line("رقم المرجع: {$this->transaction->reference_number}")
            ->action('عرض التفاصيل', url('/transactions/' . $this->transaction->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'transaction_received',
            'title'            => 'تم الاستلام',
            'body'             => "استلام {$this->transaction->amount} من {$this->senderName}",
            'transaction_id'   => $this->transaction->id,
            'reference_number' => $this->transaction->reference_number,
            'amount'           => (float) $this->transaction->amount,
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

## إضافة FCM Channel إلى Laravel

```php
// config/notification.php — أو مباشرة في AppServiceProvider

// تسجيل القناة المخصصة
// app/Providers/AppServiceProvider.php

use App\Notifications\Channels\FcmChannel;
use Illuminate\Support\Facades\Notification;

public function boot(): void
{
    Notification::extend('fcm', function ($app) {
        return new FcmChannel();
    });
}
```

## هيكل جدول الإشعارات (Database Notifications)

```bash
php artisan notifications:table
php artisan migrate
```

الجدول `notifications` يخزن الإشعارات محلياً لعرضها في التطبيق:

| العمود | النوع | الوصف |
|--------|------|-------|
| id | char(36) | UUID |
| type | string | `App\Notifications\TransactionSent` |
| notifiable_type | string | `App\Models\User` |
| notifiable_id | bigint | user id |
| data | json | محتوى الإشعار |
| read_at | timestamp | وقت القراءة (null = غير مقروء) |
| created_at | timestamp | |

## أمثلة SMS (للطوارئ — إذا لم يكن هناك FCM)

```php
// استخدام خدمة SMS كـ fallback
// لتنفيذ toSms() في Notification

public function toSms(object $notifiable): string
{
    $amount = $this->transaction->amount;
    return "تم استلام {$amount} في محفظة Beza. رقم المرجع: {$this->transaction->reference_number}";
}
```
