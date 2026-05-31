<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

final readonly class AgentActivated
{
    public function __construct(
        public string $agentId,
        public string $commissionTier,
        public int $minimumFloat,
        public int $maxTransactionLimit,
        public int $timestamp,
    ) {}
}
