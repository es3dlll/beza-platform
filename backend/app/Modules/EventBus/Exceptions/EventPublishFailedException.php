<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Exceptions;

use Exception;

final class EventPublishFailedException extends Exception
{
    public function __construct(string $eventType, string $reason, int $code = 7001)
    {
        parent::__construct("Failed to publish event {$eventType}: {$reason}", $code);
    }
}
