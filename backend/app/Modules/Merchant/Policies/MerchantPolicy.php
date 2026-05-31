<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Policies;

use App\Modules\Identity\Models\User;

final class MerchantPolicy
{
    public function onboard(User $user): bool
    {
        return $user->kyc_level !== 'T1' || $user->hasAnyRole(['admin']);
    }

    public function createInvoice(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'merchant']);
    }

    public function pay(User $user): bool
    {
        return $user->id !== null;
    }

    public function refund(User $user, string $merchantId, ?string $paidAt): bool
    {
        if ($user->hasAnyRole(['admin', 'compliance'])) {
            return true;
        }

        $isMerchant = ($user->id === $merchantId);
        $withinWindow = $paidAt ? now()->diffInHours($paidAt) <= 24 : true;

        return $isMerchant && $withinWindow;
    }

    public function viewSettlements(User $user, string $merchantId): bool
    {
        return $user->id === $merchantId || $user->hasAnyRole(['admin', 'compliance', 'finance_officer']);
    }
}
