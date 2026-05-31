<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

final readonly class TriggerAgentSettlement
{
    public function __construct(
        public string $agentId,
        public string $settlementDate,
        public int $expectedAmount,
        public int $commissionAmount,
        public int $timestamp,
    ) {}
}
