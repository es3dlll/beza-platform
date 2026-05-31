<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Services;

use App\Modules\EventBus\Contracts\AsyncEvent;
use Illuminate\Support\Str;

final class EventSerializer
{
    public function __construct(
        private readonly SchemaVersionManager $schemaManager,
    ) {}

    public function envelope(AsyncEvent $event): array
    {
        return [
            'event_id' => Str::ulid()->toBase32(),
            'event_version' => $event->getEventVersion(),
            'event_type' => $event->getEventType(),
            'timestamp' => now()->getTimestamp(),
            'source' => $event->getSource(),
            'data' => $event->getPayload(),
        ];
    }

    public function envelopeFromArray(string $eventType, array $payload, string $version = 'v1', string $source = 'internal'): array
    {
        return [
            'event_id' => Str::ulid()->toBase32(),
            'event_version' => $version,
            'event_type' => $eventType,
            'timestamp' => now()->getTimestamp(),
            'source' => $source,
            'data' => $payload,
        ];
    }

    public function deserialize(array $envelope): array
    {
        $version = $envelope['event_version'] ?? 'v1';

        if (!$this->schemaManager->isVersionSupported($version)) {
            throw new \RuntimeException("Unsupported event version: {$version}");
        }

        return $envelope;
    }
}
