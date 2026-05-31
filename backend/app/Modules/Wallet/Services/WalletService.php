<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\Wallet\ValueObjects\BalanceSnapshot;

final class WalletService
{
    public function getBalance(string $walletId): BalanceSnapshot
    {
        return new BalanceSnapshot(
            settledBalance: 0,
            pendingBalance: 0,
        );
    }
}
