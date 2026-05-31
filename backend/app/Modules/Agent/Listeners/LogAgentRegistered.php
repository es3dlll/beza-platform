<?php

declare(strict_types=1);

namespace App\Modules\Agent\Listeners;

use App\Modules\Agent\Events\AgentRegistered;
use App\Modules\AuditLog\Models\AuditLog;

final class LogAgentRegistered
{
    public function handle(AgentRegistered $event): void
    {
        AuditLog::create([
            'user_id' => $event->agent->user_id,
            'action' => 'agent_registered',
            'resource_type' => 'agent',
            'resource_id' => $event->agent->id,
            'metadata' => [
                'status' => $event->agent->status,
                'region' => $event->agent->region,
            ],
            'result' => 'success',
        ]);
    }
}
