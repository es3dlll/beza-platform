<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Services;

use App\Modules\EventBus\Contracts\AsyncEvent;
use App\Modules\EventBus\Events\MessageFailed;
use App\Modules\EventBus\Exceptions\EventPublishFailedException;
use App\Modules\EventBus\Jobs\AsyncEventHandler;
use App\Modules\EventBus\Models\EventDeliveryLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EventPublisher
{
    public function __construct(
        private readonly EventSerializer $serializer,
        private readonly ConsumerRegistry $registry,
        private readonly RetryPolicy $retryPolicy,
    ) {}

    public function publish(AsyncEvent $event, ?string $routingKey = null, int $delay = 0): string
    {
        $envelope = $this->serializer->envelope($event);
        $eventId = $envelope['event_id'];
        $eventType = $envelope['event_type'] ?? $event->getEventType();

        try {
            $consumers = $this->registry->getConsumersForEvent($eventType);

            if (empty($consumers)) {
                Log::debug("No consumers registered for event type: {$eventType}", ['event_id' => $eventId]);
                return $eventId;
            }

            foreach ($consumers as $consumer) {
                $log = EventDeliveryLog::create([
                    'id' => Str::ulid()->toBase32(),
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'status' => 'pending',
                    'payload' => $envelope,
                    'consumer_name' => $consumer->getName(),
                    'attempt' => 0,
                    'delivered_at' => now(),
                ]);

                AsyncEventHandler::dispatch($envelope, $consumer->getName(), $log->id);

                Log::debug("Event {$eventId} dispatched to consumer {$consumer->getName()}");
            }

            return $eventId;
        } catch (\Throwable $e) {
            Log::error("Failed to publish event {$eventType}: {$e->getMessage()}");

            event(new MessageFailed(
                eventId: $eventId,
                eventType: $eventType,
                consumerName: 'publisher',
                attempt: 0,
                errorMessage: $e->getMessage(),
            ));

            throw new EventPublishFailedException($eventType, $e->getMessage());
        }
    }
}
