<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

final readonly class AgentTransactionCompleted
{
    public function __construct(
        public string $agentTransactionId,
        public string $agentId,
        public string $type,
        public int $amount,
        public string $currency,
        public int $commissionAmount,
        public int $timestamp,
    ) {}
}
