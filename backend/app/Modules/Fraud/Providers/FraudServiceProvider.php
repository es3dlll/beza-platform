<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Providers;

use App\Modules\Fraud\Services\DeviceFingerprintService;
use App\Modules\Fraud\Services\FraudGuard;
use App\Modules\Fraud\Services\ScoringPipeline;
use App\Modules\Fraud\Services\VelocityService;
use Illuminate\Support\ServiceProvider;

final class FraudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VelocityService::class);
        $this->app->singleton(DeviceFingerprintService::class);
        $this->app->singleton(ScoringPipeline::class);
        $this->app->singleton(FraudGuard::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
