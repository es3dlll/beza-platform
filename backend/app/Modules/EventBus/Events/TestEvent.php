<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Events;

use App\Modules\EventBus\Contracts\AsyncEvent;

final class TestEvent implements AsyncEvent
{
    public function __construct(
        private readonly string $eventType = 'test.event',
        private readonly array $payload = ['key' => 'value'],
        private readonly string $version = 'v1',
        private readonly string $source = 'test',
    ) {}

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getEventVersion(): string
    {
        return $this->version;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
