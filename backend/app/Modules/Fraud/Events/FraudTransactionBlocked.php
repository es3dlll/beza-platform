<?php

declare(strict_types=1);

namespace Modules\Fraud\Events;

use Illuminate\Foundation\Events\Dispatchable;

class FraudTransactionBlocked
{
    use Dispatchable;

    public function __construct(
        public readonly string $fraudEventId,
        public readonly string $actorId,
        public readonly string $eventType,
        public readonly int $riskScore,
        public readonly string $reason,
    ) {}
}
