<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services\Channels;

use App\Modules\Notification\Models\NotificationMessage;
use Illuminate\Support\Facades\Log;

final class SmsChannel
{
    public function send(NotificationMessage $message): bool
    {
        Log::info('[SMS-CHANNEL] Simulated SMS send', [
            'to' => $message->user_id,
            'text' => $message->body,
            'channel' => 'sms',
        ]);
        $message->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        return true;
    }

    public function isReady(): bool
    {
        return false;
    }
}
