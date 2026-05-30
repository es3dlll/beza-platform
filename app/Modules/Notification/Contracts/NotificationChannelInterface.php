<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts;

interface NotificationChannelInterface
{
    public function send(string $recipient, string $message, array $options = []): bool;
    public function getType(): string;
}
