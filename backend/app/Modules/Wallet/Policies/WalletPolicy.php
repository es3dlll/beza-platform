<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Policies;

use App\Modules\Identity\Models\User;

final class WalletPolicy
{
    public function viewLimits(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance_officer', 'compliance']) || $user->id !== null;
    }

    public function requestIncrease(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'finance_officer', 'compliance']) || $user->kyc_level === 'T3';
    }
}
