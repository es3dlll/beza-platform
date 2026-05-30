<?php

declare(strict_types=1);

namespace App\Contracts\Notification;

interface NotificationChannelInterface
{
    public function send(string $recipient, string $message, array $options = []): bool;
    public function getType(): string;
}
