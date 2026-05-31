<?php

declare(strict_types=1);

namespace App\Policies;

use App\Modules\Identity\Models\User;

final class ExecutiveDashboardPolicy
{
    public function view(User $user): bool
    {
        return in_array($user->role ?? '', ['executive', 'admin', 'finance_director'], true);
    }
}
