<?php

declare(strict_types=1);

namespace App\Modules\Fx\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FxSuspenseSettled
{
    use Dispatchable;

    public function __construct(
        public readonly string $fxTransactionId,
        public readonly string $walletId,
        public readonly string $baseCurrency,
        public readonly string $quoteCurrency,
        public readonly int $suspenseAmount,
    ) {}
}
