<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class SettlementDue
{
    use Dispatchable;

    public function __construct(
        public readonly string $agentId,
        public readonly string $settlementDate,
        public readonly int $expectedAmount,
        public readonly int $commissionAmount,
    ) {}
}
