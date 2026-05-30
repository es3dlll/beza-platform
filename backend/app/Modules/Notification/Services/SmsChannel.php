<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Modules\Notification\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Log;

class SmsChannel implements NotificationChannelInterface
{
    private readonly string $driver;
    private readonly ?string $apiKey;
    private readonly ?string $senderId;

    public function __construct()
    {
        $this->driver = config('notifications.sms.driver', 'log');
        $this->apiKey = config('notifications.sms.api_key');
        $this->senderId = config('notifications.sms.sender_id', 'Beza');
    }

    public function send(string $recipient, string $message, array $options = []): bool
    {
        return match ($this->driver) {
            'smpp_syriatel' => $this->sendViaSyriatel($recipient, $message),
            'smpp_mtn' => $this->sendViaMtn($recipient, $message),
            'log' => $this->sendViaLog($recipient, $message),
            default => throw new \RuntimeException("Unknown SMS driver: {$this->driver}"),
        };
    }

    public function getType(): string
    {
        return 'sms';
    }

    private function sendViaSyriatel(string $recipient, string $message): bool
    {
        Log::info('[SMS Syriatel]', ['to' => $recipient, 'message' => $message]);
        return true;
    }

    private function sendViaMtn(string $recipient, string $message): bool
    {
        Log::info('[SMS MTN]', ['to' => $recipient, 'message' => $message]);
        return true;
    }

    private function sendViaLog(string $recipient, string $message): bool
    {
        Log::info('[SMS LOG]', ['to' => $recipient, 'message' => $message]);
        return true;
    }
}
