<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Consumers;

use App\Modules\EventBus\Contracts\EventConsumer;
use App\Modules\EventBus\Models\EventDeliveryLog;
use Illuminate\Support\Facades\Log;

final class AuditLogConsumer implements EventConsumer
{
    public function getName(): string
    {
        return 'audit_log';
    }

    public function handle(string $eventType, array $payload, EventDeliveryLog $log): void
    {
        $eventId = $log->event_id;

        Log::channel('cfe')->info('Async event processed', [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'consumer' => $this->getName(),
            'payload' => json_encode($payload),
        ]);
    }
}
