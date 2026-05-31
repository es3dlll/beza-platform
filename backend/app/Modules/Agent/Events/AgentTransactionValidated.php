<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

final readonly class AgentTransactionValidated
{
    public function __construct(
        public string $agentId,
        public string $transactionType,
        public int $amount,
        public string $currency,
        public bool $approved,
        public ?string $reason = null,
    ) {}
}
