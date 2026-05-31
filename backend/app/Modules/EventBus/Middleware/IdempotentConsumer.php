<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Middleware;

use App\Modules\EventBus\Models\EventDeliveryLog;
use App\Modules\EventBus\Jobs\AsyncEventHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class IdempotentConsumer
{
    private int $ttlSeconds;

    public function __construct()
    {
        $this->ttlSeconds = (int) config('event_bus.idempotency.ttl_seconds', 86400);
    }

    public function handle($job, \Closure $next): void
    {
        $eventId = null;
        $consumerName = 'unknown';

        if ($job instanceof \Illuminate\Contracts\Queue\Job) {
            $payload = $job->payload();
            $command = unserialize($payload['data']['command'] ?? '');
            if ($command instanceof AsyncEventHandler) {
                $eventId = $command->envelope['event_id'] ?? null;
                $consumerName = $command->consumerName;
            }
        } elseif ($job instanceof AsyncEventHandler) {
            $eventId = $job->envelope['event_id'] ?? null;
            $consumerName = $job->consumerName;
        }

        if ($eventId !== null) {
            $cacheKey = "consume:{$eventId}";

            if (Cache::has($cacheKey)) {
                Log::debug("IdempotentConsumer skipped duplicate event: {$eventId}");

                EventDeliveryLog::where('event_id', $eventId)
                    ->where('consumer_name', $consumerName)
                    ->update(['status' => 'skipped']);

                if ($job instanceof \Illuminate\Contracts\Queue\Job) {
                    $job->delete();
                }

                return;
            }

            Cache::put($cacheKey, true, $this->ttlSeconds);
        }

        $next($job);
    }
}
