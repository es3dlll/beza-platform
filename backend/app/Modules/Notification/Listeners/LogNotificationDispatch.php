<?php

declare(strict_types=1);

namespace App\Modules\Notification\Listeners;

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Notification\Events\NotificationDispatched;

final class LogNotificationDispatch
{
    public function handle(NotificationDispatched $event): void
    {
        $m = $event->message;
        AuditLog::create([
            'user_id' => $m->user_id,
            'action' => "notification_{$m->status}",
            'resource_type' => 'notification',
            'resource_id' => $m->id,
            'result' => $m->status === 'sent' ? 'success' : 'failure',
            'metadata' => [
                'type' => $m->type,
                'channel' => $m->channel,
                'reference_type' => $m->reference_type,
                'reference_id' => $m->reference_id,
                'failure_reason' => $m->failure_reason,
            ],
        ]);
    }
}
