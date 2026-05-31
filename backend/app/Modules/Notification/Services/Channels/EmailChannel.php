<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services\Channels;

use App\Modules\Notification\Models\NotificationMessage;
use Illuminate\Support\Facades\Log;

final class EmailChannel
{
    public function send(NotificationMessage $message): bool
    {
        Log::info('[EMAIL-CHANNEL] Simulated email send', [
            'to' => $message->user_id,
            'subject' => $message->title,
            'channel' => 'email',
        ]);
        $message->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        return true;
    }
}
