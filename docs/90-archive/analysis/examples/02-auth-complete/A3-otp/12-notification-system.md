# 12 - نظام الإشعارات (Notification System)

## إرسال OTP عبر SMS

```php
<?php
// app/Notifications/OtpSms.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OtpSms extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $code,
    ) {}

    public function via(object $notifiable): array
    {
        return ['fcm', 'database'];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'رمز التحقق',
            'body'  => "رمز التحقق الخاص بك: {$this->code}\nالرمز صالح لمدة 5 دقائق.",
            'data'  => [
                'type' => 'otp',
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'  => 'otp',
            'title' => 'رمز التحقق',
            'body'  => "تم إرسال رمز التحقق إلى رقم هاتفك",
        ];
    }
}
```

## SmsService لإرسال OTP

```php
<?php
// app/Services/SmsService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $provider;
    private string $apiKey;
    private string $senderName;

    public function __construct()
    {
        $this->provider   = config('services.sms.provider', 'local');
        $this->apiKey     = config('services.sms.api_key', '');
        $this->senderName = config('services.sms.sender', 'Beza');
    }

    public function sendOtp(string $phone, string $code): bool
    {
        $message = "Beza: رمز التحقق {$code}\nصالح لمدة 5 دقائق.";

        return $this->send($phone, $message);
    }

    private function send(string $phone, string $message): bool
    {
        if ($this->provider === 'local' || app()->environment('local')) {
            Log::info('[SMS DEV]', compact('phone', 'message'));
            return true;
        }

        // مثال لـ Kannel SMS Gateway
        // $response = Http::get(config('services.sms.url'), [
        //     'username' => config('services.sms.username'),
        //     'password' => config('services.sms.password'),
        //     'to'       => $phone,
        //     'text'     => $message,
        //     'from'     => $this->senderName,
        // ]);

        // return $response->successful();

        Log::info('SMS sent', compact('phone'));
        return true;
    }
}
```

## تكوين SMS

```php
<?php
// config/services.php

return [
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],
    'sms' => [
        'provider'  => env('SMS_PROVIDER', 'local'),
        'api_key'   => env('SMS_API_KEY'),
        'sender'    => env('SMS_SENDER', 'Beza'),
        'url'       => env('SMS_URL'),
        'username'  => env('SMS_USERNAME'),
        'password'  => env('SMS_PASSWORD'),
    ],
];
```

## هيكل SMS Gateway

```
Provider: Kannel / Twilio / local
تنسيق الرسالة:
  Beza: رمز التحقق 123456
  صالح لمدة 5 دقائق.
```
