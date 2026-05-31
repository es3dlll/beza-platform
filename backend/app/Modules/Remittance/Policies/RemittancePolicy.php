<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Policies;

use App\Modules\Identity\Models\User;

final class RemittancePolicy
{
    public function initiate(User $user): bool
    {
        return in_array($user->kyc_level ?? 'T1', ['T2', 'T3'], true) || $user->hasAnyRole(['admin', 'finance_officer']);
    }

    public function cancel(User $user, string $senderId, string $createdAt): bool
    {
        if ($user->hasAnyRole(['admin', 'compliance'])) {
            return true;
        }

        $isSender = ($user->id === $senderId);
        $withinWindow = now()->diffInMinutes($createdAt) <= 5;

        return $isSender && $withinWindow;
    }

    public function view(User $user, string $senderId): bool
    {
        return $user->id === $senderId || $user->hasAnyRole(['admin', 'compliance', 'finance_officer']);
    }
}
