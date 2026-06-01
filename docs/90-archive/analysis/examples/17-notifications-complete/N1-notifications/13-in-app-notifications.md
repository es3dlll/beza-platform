# 13 - الإشعارات داخل التطبيق (In-App)

```php
<?php

namespace App\Services\Channels;

use App\Models\User;
use App\Models\Notification;

class DatabaseChannel
{
    public function send(User $user, array $compiled, array $data): array
    {
        Notification::create([
            'type' => $data['type'] ?? 'general',
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'channel' => 'database',
            'title' => $compiled['title'],
            'body' => $compiled['body'],
            'data' => $data,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return ['success' => true];
    }
}
```

## Job معالجة الإشعارات

```php
<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\Channels\FCMChannel;
use App\Services\Channels\TwilioChannel;
use App\Services\Channels\MailChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private Notification $notification,
        private array $channels,
    ) {}

    public function handle(
        FCMChannel $fcm,
        TwilioChannel $twilio,
        MailChannel $mail,
    ): void {
        $user = $this->notification->notifiable;
        $compiled = [
            'title' => $this->notification->title,
            'body' => $this->notification->body,
        ];
        $data = $this->notification->data ?? [];

        foreach ($this->channels as $channel) {
            try {
                $result = match ($channel) {
                    'fcm' => $fcm->send($user, $compiled, $data),
                    'sms' => $twilio->send($user, $compiled, $data),
                    'email' => $mail->send($user, $compiled, $data),
                    default => ['success' => false, 'error' => "Unknown channel: {$channel}"],
                };

                $this->notification->logs()->create([
                    'channel' => $channel,
                    'provider_response' => $result,
                    'status' => $result['success'] ? 'sent' : 'failed',
                ]);
            } catch (\Throwable $e) {
                Log::error("ProcessNotification: {$e->getMessage()}");
                $this->notification->logs()->create([
                    'channel' => $channel,
                    'provider_response' => ['error' => $e->getMessage()],
                    'status' => 'failed',
                ]);
            }
        }

        $this->notification->refresh();
        $hasAnySuccess = $this->notification->logs()->where('status', 'sent')->exists();
        $hasAnyFailed = $this->notification->logs()->where('status', 'failed')->exists();

        if ($hasAnySuccess && !$hasAnyFailed) {
            $this->notification->markAsSent();
        } elseif ($hasAnyFailed && !$hasAnySuccess) {
            $this->notification->markAsFailed('All channels failed');
        }
    }
}
```
