<?php

declare(strict_types=1);

namespace App\Modules\Agent\Providers;

use App\Modules\Agent\Services\AgentService;
use App\Modules\Agent\Services\CashInOutService;
use App\Modules\Agent\Services\CommissionService;
use App\Modules\Agent\Services\SettlementService;
use Illuminate\Support\ServiceProvider;

final class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentService::class);
        $this->app->singleton(CommissionService::class);
        $this->app->singleton(CashInOutService::class);
        $this->app->singleton(SettlementService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
