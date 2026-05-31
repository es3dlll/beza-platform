<?php

declare(strict_types=1);

namespace App\Modules\Agent\Policies;

use App\Modules\Identity\Models\User;

final class AgentPolicy
{
    public function onboard(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function float(User $user, string $agentId): bool
    {
        return $user->id === $agentId || $user->hasAnyRole(['admin', 'treasury']);
    }

    public function commissions(User $user, string $agentId): bool
    {
        return $user->id === $agentId || $user->hasAnyRole(['admin', 'accountant']);
    }

    public function settle(User $user, string $agentId, string $tier): bool
    {
        if ($user->hasAnyRole(['admin', 'finance_supervisor'])) {
            return true;
        }
        return $user->id === $agentId && in_array($tier, ['Gold', 'Platinum'], true);
    }
}
