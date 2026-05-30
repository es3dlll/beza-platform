<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Agent\Services\AgentService;

final class AgentRefreshLiquidityScores extends Command
{
    protected $signature = 'agent:refresh-liquidity';
    protected $description = 'Refresh liquidity scores for all agents';

    public function handle(AgentService $agentService): int
    {
        $count = $agentService->refreshLiquidityScores();
        $this->info("Liquidity scores refreshed for {$count} agents");
        return self::SUCCESS;
    }
}
