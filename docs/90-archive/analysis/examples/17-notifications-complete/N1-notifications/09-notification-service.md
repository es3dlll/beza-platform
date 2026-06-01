# 09 - خدمة الإشعارات (Notification Service)

```php
<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\Channels\FCMChannel;
use App\Services\Channels\TwilioChannel;
use App\Services\Channels\MailChannel;
use App\Services\Channels\DatabaseChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private array $channels = [];

    public function __construct(
        private FCMChannel $fcm,
        private TwilioChannel $twilio,
        private MailChannel $mail,
        private DatabaseChannel $database,
    ) {
        $this->channels = [
            'fcm' => $this->fcm,
            'sms' => $this->twilio,
            'email' => $this->mail,
            'database' => $this->database,
        ];
    }

    public function send(
        User $user,
        string $type,
        array $data = [],
        ?array $channels = null,
        int $priority = 0
    ): Notification {
        $template = NotificationTemplate::where('type', $type)->firstOrFail();
        $compiled = $template->compile($data);
        $targetChannels = $channels ?? $template->channels;

        $notification = Notification::create([
            'type' => $type,
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'channel' => $targetChannels[0] ?? 'database',
            'title' => $compiled['title'],
            'body' => $compiled['body'],
            'data' => $data,
            'status' => 'pending',
        ]);

        dispatch(new \App\Jobs\ProcessNotification($notification, $targetChannels));

        return $notification;
    }

    public function sendNow(
        User $user,
        string $type,
        array $data = [],
        ?array $channels = null,
    ): array {
        $template = NotificationTemplate::where('type', $type)->firstOrFail();
        $compiled = $template->compile($data);
        $targetChannels = $channels ?? $template->channels;
        $results = [];

        foreach ($targetChannels as $channel) {
            if (isset($this->channels[$channel])) {
                try {
                    $result = $this->channels[$channel]->send($user, $compiled, $data);
                    $results[$channel] = $result;
                } catch (\Throwable $e) {
                    Log::error("Notification failed on {$channel}: {$e->getMessage()}");
                    $results[$channel] = ['success' => false, 'error' => $e->getMessage()];
                }
            }
        }

        return $results;
    }

    public function sendBulk(array $users, string $type, array $data = []): void
    {
        foreach ($users as $user) {
            $this->send($user, $type, $data);
        }
    }
}
```
