<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Providers;

use App\Modules\Wallet\Services\DynamicLimitService;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WalletService::class);
        $this->app->singleton(DynamicLimitService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
