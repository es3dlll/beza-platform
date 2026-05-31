<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Events;

use App\Modules\Wallet\ValueObjects\WalletLimit;

final readonly class LimitExceeded
{
    public function __construct(
        public string $userId,
        public int $amount,
        public WalletLimit $limit,
    ) {}
}
