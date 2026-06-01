# 12 - نظام الإشعارات (FCM + SMS + Email)

## إشعار الترحيب مع الهدية

```php
<?php
// app/Notifications/WelcomeWithBonus.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeWithBonus extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $sypWalletNumber,
        private readonly string $usdWalletNumber,
        private readonly float  $bonusAmount,
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
            ->subject('مرحباً بك في Beza — هدية 5$')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('شكراً لتسجيلك في منصة Beza للمدفوعات الرقمية.')
            ->line("رقم محفظة SYP: {$this->sypWalletNumber}")
            ->line("رقم محفظة USD: {$this->usdWalletNumber}")
            ->line("تم إضافة هدية ترحيبية بقيمة {$this->bonusAmount} USD إلى محفظتك!")
            ->line('يمكنك استخدام الرصيد في التحويلات والصرافة فوراً.')
            ->action('بدء الاستخدام', url('/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'welcome_bonus',
            'title'            => 'مرحباً بك في Beza 🎉',
            'body'             => "تم إنشاء محفظتك وإضافة هدية {$this->bonusAmount} USD",
            'syp_wallet'       => $this->sypWalletNumber,
            'usd_wallet'       => $this->usdWalletNumber,
            'bonus_amount'     => $this->bonusAmount,
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

## محتوى الإشعارات

| الإشعار | المحتوى | القناة |
|---------|---------|--------|
| ترحيب | "مرحباً بك! تم إنشاء محفظتك وإضافة هدية 5$" | FCM + Email + DB |
| هدية | "تم إيداع 5$ كهدية ترحيبية في محفظتك" | FCM + DB |
