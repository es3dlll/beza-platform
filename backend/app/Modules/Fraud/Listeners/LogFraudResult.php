<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Listeners;

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Fraud\Events\LiquidityApproved;
use App\Modules\Fraud\Events\LiquidityCompleted;

final class LogFraudResult
{
    public function handle(LiquidityApproved|LiquidityCompleted $event): void
    {
        if ($event instanceof LiquidityApproved) {
            AuditLog::create([
                'user_id' => $event->agent->user_id,
                'action' => 'fraud_check_approved',
                'resource_type' => 'liquidity',
                'resource_id' => $event->requestId,
                'metadata' => [
                    'score' => $event->riskScore,
                    'amount_fils' => $event->amount->fils(),
                    'currency' => $event->amount->currency()->value,
                    'agent_id' => $event->agent->id,
                ],
                'result' => 'success',
            ]);
        }

        if ($event instanceof LiquidityCompleted) {
            AuditLog::create([
                'user_id' => $event->agentId,
                'action' => 'liquidity_completed',
                'resource_type' => 'liquidity',
                'resource_id' => $event->requestId,
                'metadata' => [
                    'amount_fils' => $event->amountFils,
                    'ledger_entry_id' => $event->ledgerEntry->id,
                    'status' => $event->status,
                ],
                'result' => 'success',
            ]);
        }
    }
}
