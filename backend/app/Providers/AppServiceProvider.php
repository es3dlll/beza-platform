<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip() ?? 'unknown');
        });

        RateLimiter::for('transfers', function (Request $request): Limit {
            $userId = $request->user()?->id ?? 'guest';
            return Limit::perMinute(10)->by($userId);
        });

        RateLimiter::for('api', function (Request $request): Limit {
            $key = $request->user()?->id ?? $request->ip() ?? 'guest';
            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('notifications', function (Request $request): Limit {
            $userId = $request->user()?->id ?? 'guest';
            return Limit::perMinute(30)->by($userId);
        });

        RateLimiter::for('analytics', function (Request $request): Limit {
            $userId = $request->user()?->id ?? 'guest';
            return Limit::perMinute(20)->by($userId);
        });
    }
}
