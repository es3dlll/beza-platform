<?php

declare(strict_types=1);

namespace App\Modules\Agent\Listeners;

use App\Modules\Agent\Events\CommissionCalculated;
use App\Modules\AuditLog\Models\AuditLog;

final class LogCommissionCalculated
{
    public function handle(CommissionCalculated $event): void
    {
        AuditLog::create([
            'user_id' => $event->agent->user_id,
            'action' => 'commission_calculated',
            'resource_type' => 'agent',
            'resource_id' => $event->agent->id,
            'metadata' => [
                'transfer_amount_fils' => $event->transferAmount->fils(),
                'commission_fils' => $event->commission->fils(),
                'currency' => $event->commission->currency()->value,
                'client_type' => $event->clientType,
            ],
            'result' => 'success',
        ]);
    }
}
