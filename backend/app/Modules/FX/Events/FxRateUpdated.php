<?php

declare(strict_types=1);

namespace App\Modules\Fx\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FxRateUpdated
{
    use Dispatchable;

    public function __construct(
        public readonly string $rateSourceId,
        public readonly string $baseCurrency,
        public readonly string $quoteCurrency,
        public readonly int $buyRate,
        public readonly int $sellRate,
        public readonly int $spreadBps,
    ) {}
}
