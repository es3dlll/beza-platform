<?php

declare(strict_types=1);

namespace App\Modules\Agent\Listeners;

use App\Modules\Agent\Events\AgentSuspendedCompliance;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Services\AgentService;
use Illuminate\Support\Facades\Log;

final readonly class ComplianceTierListener
{
    public function __construct(private AgentService $agentService) {}

    public function handle(AgentSuspendedCompliance $event): void
    {
        Log::warning('ComplianceTierListener: suspending agent', [
            'agent_id' => $event->agentId,
            'reason' => $event->reason,
            'case_id' => $event->caseId,
        ]);

        $this->agentService->suspend($event->agentId);
    }
}
