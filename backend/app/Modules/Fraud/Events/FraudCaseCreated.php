<?php

declare(strict_types=1);

namespace Modules\Fraud\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FraudCaseCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $fraudCaseId,
        public readonly string $fraudEventId,
        public readonly string $actorId,
        public readonly string $severity,
        public readonly int $riskScore,
    ) {}
}
