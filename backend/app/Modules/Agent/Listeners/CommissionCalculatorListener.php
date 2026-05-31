<?php

declare(strict_types=1);

namespace App\Modules\Agent\Listeners;

use App\Modules\Agent\Events\AgentTransactionCompleted;
use App\Modules\Agent\Events\CommissionReadyForPayout;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\ValueObjects\CommissionTier;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final readonly class CommissionCalculatorListener
{
    public function handle(AgentTransactionCompleted $event): void
    {
        Log::info('CommissionCalculatorListener: calculating commission', [
            'agent_id' => $event->agentId,
            'type' => $event->type,
            'amount' => $event->amount,
        ]);

        $agent = Agent::find($event->agentId);
        if (!$agent) {
            return;
        }

        $tier = CommissionTier::fromString($agent->commission_tier ?? 'Bronze');
        $commission = $tier->calculateCommission($event->type, $event->amount);

        Log::info('CommissionCalculatorListener: commission calculated', [
            'agent_id' => $event->agentId,
            'commission' => $commission,
            'tier' => $tier->tier(),
        ]);

        if ($commission > 0) {
            Event::dispatch(new CommissionReadyForPayout(
                agentId: $event->agentId,
                totalCommission: $commission,
                currency: $event->currency,
                timestamp: now()->getTimestamp(),
            ));
        }
    }
}
