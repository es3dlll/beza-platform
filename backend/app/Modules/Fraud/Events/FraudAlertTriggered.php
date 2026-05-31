<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FraudAlertTriggered
{
    use Dispatchable;

    public function __construct(
        public readonly string $decisionId,
        public readonly string $walletId,
        public readonly string $ruleId,
        public readonly string $action,
        public readonly int $score,
        public readonly string $reason,
        public readonly ?string $contextId = null,
    ) {}
}
