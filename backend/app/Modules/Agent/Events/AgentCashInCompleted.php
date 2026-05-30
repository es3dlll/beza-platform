<?php

declare(strict_types=1);

namespace Modules\Agent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AgentCashInCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $agentId,
        public readonly string $userWalletId,
        public readonly int $amount,
        public readonly int $commission,
        public readonly ?string $referenceId,
    ) {}
}
