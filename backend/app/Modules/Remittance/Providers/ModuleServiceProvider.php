<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Providers;

use App\Modules\Remittance\Events\FXRateLocked;
use App\Modules\Remittance\Events\InitiateLedgerTransfer;
use App\Modules\Remittance\Listeners\SanctionScreeningListener;
use App\Modules\Remittance\Listeners\ThresholdReportingListener;
use App\Modules\Remittance\Services\RemittanceEngine;
use App\Modules\Remittance\Services\RemittanceQuoteService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RemittanceEngine::class);
        $this->app->singleton(RemittanceQuoteService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        Event::listen(
            FXRateLocked::class,
            SanctionScreeningListener::class,
        );

        Event::listen(
            InitiateLedgerTransfer::class,
            ThresholdReportingListener::class,
        );
    }
}
