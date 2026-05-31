<?php

declare(strict_types=1);

namespace App\Modules\Fx\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class FxConversionCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $fxTransactionId,
        public readonly string $walletId,
        public readonly string $baseCurrency,
        public readonly string $quoteCurrency,
        public readonly int $debitAmount,
        public readonly int $creditAmount,
        public readonly int $rateUsed,
        public readonly int $spreadBps,
        public readonly string $cfeTransactionId,
    ) {}
}
