<?php

declare(strict_types=1);

namespace Modules\Settlement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SettlementCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $settlementId,
        public readonly string $referenceType,
        public readonly string $referenceId,
        public readonly int $netAmount,
        public readonly string $currency,
    ) {}
}
