<?php

declare(strict_types=1);

namespace App\Modules\Bills\Policies;

use App\Modules\Bills\Models\Bill;
use App\Models\User;

final class BillPolicy
{
    public function view(User $user, Bill $bill): bool
    {
        return $user->id === $bill->user_id || $user->hasRole('admin');
    }

    public function pay(User $user, Bill $bill): bool
    {
        return $user->id === $bill->user_id || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function manage(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
