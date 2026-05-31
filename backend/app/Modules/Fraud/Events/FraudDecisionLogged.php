<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FraudDecisionLogged
{
    use Dispatchable;

    public function __construct(
        public readonly string $decisionId,
        public readonly string $walletId,
        public readonly string $action,
        public readonly int $scoreBefore,
        public readonly int $scoreAfter,
        public readonly string $reason,
    ) {}
}
