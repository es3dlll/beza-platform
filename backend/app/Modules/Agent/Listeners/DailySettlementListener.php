<?php

declare(strict_types=1);

namespace App\Modules\Agent\Listeners;

use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentTransaction;
use App\Modules\Agent\Services\SettlementService;
use Illuminate\Support\Facades\Log;

final readonly class DailySettlementListener
{
    public function __construct(private SettlementService $settlementService) {}

    public function handle(): void
    {
        $date = now()->subDay()->toDateString();

        Log::info('DailySettlementListener: running daily settlement', ['date' => $date]);

        $agents = Agent::where('status', 'active')->get();
        $results = [];

        foreach ($agents as $agent) {
            try {
                $settlement = $this->settlementService->generateForAgent($agent, $date);
                $results[] = [
                    'agent_id' => $agent->id,
                    'settlement_id' => $settlement->id,
                    'status' => $settlement->status,
                ];
            } catch (\Throwable $e) {
                Log::error('DailySettlementListener: settlement failed', [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('DailySettlementListener: completed', [
            'date' => $date,
            'total_agents' => count($agents),
            'settled' => count($results),
        ]);
    }
}
