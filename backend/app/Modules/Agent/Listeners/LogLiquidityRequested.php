<?php

declare(strict_types=1);

namespace App\Modules\Agent\Listeners;

use App\Modules\Agent\Events\LiquidityRequested;
use App\Modules\AuditLog\Models\AuditLog;

final class LogLiquidityRequested
{
    public function handle(LiquidityRequested $event): void
    {
        AuditLog::create([
            'user_id' => $event->agent->user_id,
            'action' => 'liquidity_requested',
            'resource_type' => 'agent',
            'resource_id' => $event->agent->id,
            'metadata' => [
                'amount_fils' => $event->amount->fils(),
                'currency' => $event->amount->currency()->value,
                'approved' => $event->approved,
                'request_id' => $event->requestId,
            ],
            'result' => $event->approved ? 'success' : 'failed',
        ]);
    }
}
