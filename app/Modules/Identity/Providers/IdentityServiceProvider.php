<?php

declare(strict_types=1);

namespace Modules\Identity\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Identity\Repositories\DeviceRepository;
use Modules\Identity\Repositories\OtpRepository;
use Modules\Identity\Repositories\UserRepository;
use Modules\Identity\Services\IdentityService;
use Modules\Identity\Services\OtpService;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserRepository::class);
        $this->app->singleton(OtpRepository::class);
        $this->app->singleton(DeviceRepository::class);
        $this->app->singleton(OtpService::class);
        $this->app->singleton(IdentityService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'identity');

        $this->publishes([
            __DIR__ . '/../Database/Migrations' => database_path('migrations'),
        ], 'identity-migrations');
    }
}
