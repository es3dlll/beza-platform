<?php

declare(strict_types=1);

namespace App\Modules\Agent\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class CommissionCalculated
{
    use Dispatchable;

    public function __construct(
        public readonly string $agentTransactionId,
        public readonly string $agentId,
        public readonly int $amount,
        public readonly int $rateBps,
        public readonly string $currency,
    ) {}
}
