<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class MessageFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly string $consumerName,
        public readonly int $attempt,
        public readonly string $errorMessage,
    ) {}
}
