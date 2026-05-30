<?php

declare(strict_types=1);

namespace Modules\Fraud\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Fraud\Services\FraudEngine;
use Modules\Fraud\Services\VelocityCheckService;
use Modules\Fraud\Services\GeolocationAnomalyService;
use Modules\Fraud\Services\DeviceFingerprintService;
use Modules\Fraud\Services\SanctionsScreeningService;
use Modules\Fraud\Repositories\FraudRuleRepository;
use Modules\Fraud\Repositories\FraudEventRepository;
use Modules\Fraud\Repositories\FraudCaseRepository;
use Modules\Fraud\Repositories\FraudBlacklistRepository;

class FraudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FraudRuleRepository::class);
        $this->app->singleton(FraudEventRepository::class);
        $this->app->singleton(FraudCaseRepository::class);
        $this->app->singleton(FraudBlacklistRepository::class);

        $this->app->singleton(VelocityCheckService::class);
        $this->app->singleton(GeolocationAnomalyService::class);
        $this->app->singleton(DeviceFingerprintService::class);
        $this->app->singleton(SanctionsScreeningService::class);

        $this->app->singleton(FraudEngine::class);
    }
}
