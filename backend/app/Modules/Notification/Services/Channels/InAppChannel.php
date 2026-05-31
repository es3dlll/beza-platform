<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services\Channels;

use App\Modules\Notification\Models\NotificationMessage;

final class InAppChannel
{
    public function send(NotificationMessage $message): bool
    {
        $message->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        return true;
    }
}
