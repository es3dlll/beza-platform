<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Events\NotificationDispatched;
use App\Modules\Notification\Models\NotificationMessage;
use App\Modules\Notification\Services\Channels\EmailChannel;
use App\Modules\Notification\Services\Channels\InAppChannel;
use App\Modules\Notification\Services\Channels\SmsChannel;
use Illuminate\Support\Facades\DB;

final class NotificationDispatcher
{
    private array $channels = [];

    public function __construct(
        private readonly InAppChannel $inApp,
        private readonly EmailChannel $email,
        private readonly SmsChannel $sms,
    ) {
        $this->channels = [
            'in_app' => $this->inApp,
            'email' => $this->email,
            'sms' => $this->sms,
        ];
    }

    public function send(
        string $userId,
        string $type,
        string $title,
        string $body,
        string $channel = 'in_app',
        ?array $data = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): NotificationMessage {
        $message = NotificationMessage::create([
            'user_id' => $userId,
            'type' => $type,
            'channel' => $channel,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'status' => 'pending',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        $this->dispatchMessage($message);

        return $message->fresh();
    }

    public function sendMultiChannel(
        string $userId,
        string $type,
        string $title,
        string $body,
        array $channels = ['in_app'],
        ?array $data = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): array {
        $results = [];
        foreach ($channels as $channel) {
            $msg = NotificationMessage::create([
                'user_id' => $userId,
                'type' => $type,
                'channel' => $channel,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'status' => 'pending',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
            $results[] = $this->dispatchMessage($msg);
        }
        return $results;
    }

    public function sendBulk(array $recipients, string $type, string $title, string $body, string $channel = 'in_app'): int
    {
        $sent = 0;
        foreach ($recipients as $userId) {
            try {
                $this->send($userId, $type, $title, $body, $channel);
                $sent++;
            } catch (\Throwable) {
                continue;
            }
        }
        return $sent;
    }

    private function dispatchMessage(NotificationMessage $message): NotificationMessage
    {
        $channel = $this->channels[$message->channel] ?? null;
        if (!$channel) {
            $message->update(['status' => 'failed', 'failed_at' => now(), 'failure_reason' => 'قناة غير معروفة']);
            event(new NotificationDispatched($message));
            return $message;
        }

        try {
            $channel->send($message);
            event(new NotificationDispatched($message));
        } catch (\Throwable $e) {
            $message->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $e->getMessage(),
            ]);
            event(new NotificationDispatched($message));
        }

        return $message->fresh();
    }
}
