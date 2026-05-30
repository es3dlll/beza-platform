<?php

declare(strict_types=1);

namespace Modules\Wallet\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WalletTransferInitiated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $fromWalletId,
        public readonly string $toWalletId,
        public readonly int $amount,
        public readonly int $fee,
        public readonly string $currency,
        public readonly string $cfeTransactionId,
    ) {}
}
