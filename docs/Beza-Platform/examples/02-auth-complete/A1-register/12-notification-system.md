# 12 - نظام الإشعارات (Notification System)

## WelcomeSms Notification

```php
<?php
// app/Notifications/WelcomeSms.php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WelcomeSms extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $user,
    ) {}

    public function via(object $notifiable): array
    {
        return ['fcm', 'database'];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'مرحباً بك في Beza',
            'body'  => "أهلاً {$this->user->name}، تم إنشاء حسابك بنجاح. لديك 5 USD هدية ترحيب في محفظتك!",
            'data'  => [
                'type' => 'welcome',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'  => 'welcome',
            'title' => 'مرحباً بك في Beza',
            'body'  => "أهلاً {$this->user->name}، تم إنشاء حسابك بنجاح. لديك 5 USD في محفظتك!",
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
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!$notifiable->fcm_token) {
            return;
        }

        $data = $notification->toFcm($notifiable);

        try {
            Http::withToken(config('services.fcm.server_key'))
                ->post('https://fcm.googleapis.com/fcm/send', [
                    'to'           => $notifiable->fcm_token,
                    'notification' => [
                        'title' => $data['title'],
                        'body'  => $data['body'],
                        'sound' => 'default',
                    ],
                    'data'     => $data['data'] ?? [],
                    'priority' => 'high',
                ]);
        } catch (\Throwable $e) {
            Log::error('FCM فشل إرسال', [
                'user_id' => $notifiable->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
```

## تسجيل FCM Channel

```php
<?php
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

## إرسال SMS الترحيبي

```php
// في SmsService
public function sendWelcome(User $user): void
{
    $message = "أهلاً {$user->name}!\n"
        . "مرحباً بك في Beza. تم إنشاء محفظتك بنجاح.\n"
        . "رصيدك الترحيبي: 5 USD\n"
        . "حمّل التطبيق: https://beza.app/download";

    // إرسال عبر SMS Gateway
}
```
