<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Jobs;

use App\Modules\EventBus\Models\DeadLetterEvent;
use App\Modules\EventBus\Models\EventDeliveryLog;
use App\Modules\EventBus\Services\ConsumerRegistry;
use App\Modules\EventBus\Services\EventSerializer;
use App\Modules\EventBus\Services\RetryPolicy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AsyncEventHandler implements ShouldQueue
{
    use Dispatchable, Queueable;

    public array $envelope;
    public string $consumerName;
    public string $logId;

    public int $tries = 1;
    public int $maxExceptions = 1;

    public function __construct(array $envelope, string $consumerName, string $logId)
    {
        $this->envelope = $envelope;
        $this->consumerName = $consumerName;
        $this->logId = $logId;
    }

    public function handle(ConsumerRegistry $registry, RetryPolicy $retryPolicy, EventSerializer $serializer): void
    {
        $log = EventDeliveryLog::find($this->logId);

        if ($log === null) {
            Log::warning("EventDeliveryLog not found: {$this->logId}");
            return;
        }

        $eventId = $this->envelope['event_id'] ?? null;
        $ttl = (int) config('event_bus.idempotency.ttl_seconds', 86400);
        $cacheKey = $eventId !== null ? "consume:{$eventId}:{$this->consumerName}" : null;

        if ($cacheKey !== null && Cache::has($cacheKey)) {
            Log::debug("IdempotentConsumer skipped duplicate event: {$eventId} for {$this->consumerName}");
            $log->update(['status' => 'skipped']);
            return;
        }

        try {
            $envelope = $serializer->deserialize($this->envelope);
            $eventType = $envelope['event_type'];

            $consumer = $registry->getConsumer($this->consumerName);

            $log->update([
                'status' => 'processing',
                'attempt' => $log->attempt + 1,
            ]);

            $consumer->handle($eventType, $envelope['data'] ?? [], $log);

            $log->update([
                'status' => 'consumed',
                'consumed_at' => now(),
            ]);

            if ($cacheKey !== null) {
                Cache::put($cacheKey, true, $ttl);
            }

            Log::debug("Event {$eventId} consumed by {$this->consumerName}");
        } catch (\Throwable $e) {
            $attempt = $log->attempt + 1;

            Log::error("Consumer {$this->consumerName} failed for event {$eventId} (attempt {$attempt}): {$e->getMessage()}");

            $log->update([
                'status' => 'failed',
                'attempt' => $attempt,
                'error_message' => $e->getMessage(),
            ]);

            if ($retryPolicy->canRetry($attempt)) {
                $delaySeconds = $retryPolicy->getDelaySeconds($attempt);

                Log::debug("Scheduling retry {$attempt} for event {$eventId} in {$delaySeconds}s");

                self::dispatch($this->envelope, $this->consumerName, $this->logId)
                    ->delay(now()->addSeconds($delaySeconds));
            } else {
                $this->moveToDeadLetter($e);
            }
        }
    }

    private function moveToDeadLetter(\Throwable $e): void
    {
        DeadLetterEvent::create([
            'id' => Str::ulid()->toBase32(),
            'event_id' => $this->envelope['event_id'],
            'event_type' => $this->envelope['event_type'],
            'consumer_name' => $this->consumerName,
            'payload' => $this->envelope,
            'headers' => [],
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString(),
            'attempts' => config('event_bus.retry.max_attempts', 3),
            'status' => 'pending',
            'failed_at' => now(),
        ]);

        EventDeliveryLog::where('id', $this->logId)->update(['status' => 'dead_letter']);

        Log::error("Event {$this->envelope['event_id']} moved to DLQ after max retries");
    }
}
