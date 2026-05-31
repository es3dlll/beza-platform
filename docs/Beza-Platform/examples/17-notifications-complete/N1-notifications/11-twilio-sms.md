# 11 - تكامل Twilio (Twilio SMS)

```php
<?php

namespace App\Services\Channels;

use App\Models\User;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Facades\Log;

class TwilioChannel
{
    private TwilioClient $client;
    private string $from;

    public function __construct()
    {
        $this->client = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->from = config('services.twilio.from');
    }

    public function send(User $user, array $compiled, array $data): array
    {
        $phone = $user->phone;
        if (!$phone) {
            return ['success' => false, 'error' => 'User has no phone number'];
        }

        try {
            $message = $this->client->messages->create(
                $phone,
                [
                    'from' => $this->from,
                    'body' => $compiled['body'],
                ]
            );

            return [
                'success' => true,
                'sid' => $message->sid,
                'status' => $message->status,
            ];
        } catch (\Twilio\Exceptions\TwilioException $e) {
            Log::error("Twilio SMS failed for {$phone}: {$e->getMessage()}");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendOTP(User $user, string $otp): array
    {
        $compiled = [
            'title' => 'رمز التحقق Beza',
            'body' => "رمز التحقق Beza: {$otp}\nرمز صالح لمدة 5 دقائق.\nإذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة.",
        ];

        return $this->send($user, $compiled, ['type' => 'otp']);
    }

    public function sendAlert(User $user, string $message): array
    {
        $compiled = [
            'title' => 'تنبيه أمني',
            'body' => $message,
        ];

        return $this->send($user, $compiled, ['type' => 'security_alert']);
    }
}
```
