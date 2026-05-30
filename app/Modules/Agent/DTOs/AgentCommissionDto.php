<?php

declare(strict_types=1);

namespace Modules\Agent\DTOs;

final class AgentCommissionDto
{
    public function __construct(
        public readonly string $agentId,
        public readonly string $transactionId,
        public readonly int $amount,
        public readonly string $type,
        public readonly string $currency = 'SYP',
    ) {}
}
