<?php

declare(strict_types=1);

namespace Modules\FX\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FxRateUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly string $rateId,
        public readonly string $baseCurrency,
        public readonly string $quoteCurrency,
        public readonly float $midRate,
        public readonly string $rateType,
    ) {}
}
