<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Policies;

use App\Modules\Identity\Models\User;

final class CompliancePolicy
{
    public function view(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'compliance', 'auditor']);
    }

    public function review(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'compliance']);
    }

    public function rules(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'security']);
    }

    public function sanctions(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'compliance']);
    }
}
