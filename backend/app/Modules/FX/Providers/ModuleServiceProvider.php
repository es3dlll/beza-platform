<?php

declare(strict_types=1);

namespace App\Modules\FX\Providers;

use App\Modules\FX\Services\FXRateProvider;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function register(): void
    {
        $this->app->singleton(FXRateProvider::class);
    }
}
