<?php

declare(strict_types=1);

namespace App\Modules\Agent\Listeners;

use App\Modules\Agent\Events\AgentTransactionCompleted;
use App\Modules\Agent\Events\LowFloatWarning;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Services\AgentLiquidityEngine;
use App\Modules\Agent\ValueObjects\FloatBalance;
use Illuminate\Support\Facades\Log;

final readonly class FloatSyncListener
{
    public function __construct(private AgentLiquidityEngine $engine) {}

    public function handle(AgentTransactionCompleted $event): void
    {
        Log::info('FloatSyncListener: syncing float', [
            'agent_id' => $event->agentId,
            'type' => $event->type,
            'amount' => $event->amount,
        ]);

        $this->engine->processTransactionCompletion(
            agentId: $event->agentId,
            type: $event->type,
            amount: $event->amount,
        );

        $float = $this->engine->getFloatStatus($event->agentId);
        if ($float['below_minimum']) {
            Log::warning('FloatSyncListener: low float warning', [
                'agent_id' => $event->agentId,
                'available' => $float['available'],
                'minimum' => $float['minimum_required'],
            ]);
        }
    }
}
