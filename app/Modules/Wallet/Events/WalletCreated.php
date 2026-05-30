<?php

declare(strict_types=1);

namespace Modules\Wallet\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WalletCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $walletId,
        public readonly string $userId,
        public readonly string $currency,
    ) {}
}
