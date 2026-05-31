<?php

declare(strict_types=1);

namespace App\Modules\Fx\Providers;

use App\Modules\Fx\Services\ConversionService;
use App\Modules\Fx\Services\RateLockService;
use App\Modules\Fx\Services\RateSyncService;
use App\Modules\Fx\Services\SpreadService;
use Illuminate\Support\ServiceProvider;

final class FxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RateLockService::class);
        $this->app->singleton(SpreadService::class);
        $this->app->singleton(RateSyncService::class);
        $this->app->singleton(ConversionService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
