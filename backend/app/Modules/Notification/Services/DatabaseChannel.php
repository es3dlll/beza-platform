<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Modules\Notification\Contracts\NotificationChannelInterface;
use Modules\Notification\Models\Notification;

final class DatabaseChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $message, array $options = []): bool
    {
        Notification::create([
            'user_id' => $recipient,
            'type' => $options['type'] ?? 'general',
            'channel' => 'database',
            'title' => $options['title'] ?? 'Notification',
            'body' => $message,
            'data' => $options['data'] ?? null,
            'action_url' => $options['action_url'] ?? null,
            'icon' => $options['icon'] ?? null,
            'sent_at' => now(),
        ]);

        return true;
    }

    public function getType(): string
    {
        return 'database';
    }
}
