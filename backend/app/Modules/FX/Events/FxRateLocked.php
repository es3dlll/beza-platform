<?php

declare(strict_types=1);

namespace App\Modules\Fx\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FxRateLocked
{
    use Dispatchable;

    public function __construct(
        public readonly string $walletId,
        public readonly string $baseCurrency,
        public readonly string $quoteCurrency,
        public readonly int $amount,
        public readonly int $lockedRate,
        public readonly int $spreadBps,
        public readonly string $expiresAt,
    ) {}
}
