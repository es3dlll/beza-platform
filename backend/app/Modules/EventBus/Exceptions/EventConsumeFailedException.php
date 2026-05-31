<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Exceptions;

use Exception;

final class EventConsumeFailedException extends Exception
{
    public function __construct(string $eventType, string $consumerName, string $reason, int $code = 7002)
    {
        parent::__construct("Consumer {$consumerName} failed for event {$eventType}: {$reason}", $code);
    }
}
