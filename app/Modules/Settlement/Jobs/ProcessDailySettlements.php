<?php

declare(strict_types=1);

namespace Modules\Settlement\Jobs;

use Modules\Agent\Models\Agent;
use Modules\Agent\Repositories\AgentRepository;
use Modules\Settlement\Services\AgentSettlementService;
use Modules\Settlement\Services\SettlementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessDailySettlements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AgentSettlementService $agentSettlement, SettlementService $settlements, AgentRepository $agents): void
    {
        $approvedAgents = $agents->findAllApproved();

        foreach ($approvedAgents as $agent) {
            try {
                $cashInToday = $agents->todayTotal($agent->id, 'cash_in');
                $cashOutToday = $agents->todayTotal($agent->id, 'cash_out');

                $pendingCommissions = \Modules\Agent\Models\AgentCommission::where('agent_id', $agent->id)
                    ->where('status', 'pending')
                    ->sum('amount');

                $s = $agentSettlement->settleDailyAgentNet(
                    $agent->id,
                    $cashInToday,
                    $cashOutToday,
                    $pendingCommissions,
                );

                $result = $settlements->execute($s->id);

                if ($result->success) {
                    \Modules\Agent\Models\AgentCommission::where('agent_id', $agent->id)
                        ->where('status', 'pending')
                        ->update(['status' => 'settled', 'settled_at' => now()]);
                }

                logger("Daily settlement for agent {$agent->id}: " . ($result->success ? 'completed' : 'failed'));
            } catch (\Exception $e) {
                logger("Daily settlement failed for agent {$agent->id}: {$e->getMessage()}");
            }
        }
    }
}
