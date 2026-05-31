<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

final class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if (in_array($user->role ?? '', ['admin', 'finance_officer'], true)) {
                return true;
            }

            if (in_array($ability, ['view-any', 'view', 'run'], true)) {
                return false;
            }

            return null;
        });
    }
}
