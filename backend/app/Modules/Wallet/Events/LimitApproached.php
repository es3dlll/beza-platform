<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Events;

use App\Modules\Wallet\ValueObjects\WalletLimit;

final readonly class LimitApproached
{
    public function __construct(
        public string $userId,
        public WalletLimit $limit,
    ) {}
}
