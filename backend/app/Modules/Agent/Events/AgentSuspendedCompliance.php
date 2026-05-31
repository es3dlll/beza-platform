<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

final readonly class AgentSuspendedCompliance
{
    public function __construct(
        public string $agentId,
        public string $reason,
        public string $caseId,
        public int $timestamp,
    ) {}
}
