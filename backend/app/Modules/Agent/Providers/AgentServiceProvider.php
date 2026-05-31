<?php

declare(strict_types=1);

namespace App\Modules\Agent\Providers;

use App\Modules\Agent\Events\AgentTransactionCompleted;
use App\Modules\Agent\Events\CashInCompleted;
use App\Modules\Agent\Events\CashOutCompleted;
use App\Modules\Agent\Listeners\CommissionCalculatorListener;
use App\Modules\Agent\Listeners\ComplianceTierListener;
use App\Modules\Agent\Listeners\DailySettlementListener;
use App\Modules\Agent\Listeners\FloatSyncListener;
use App\Modules\Agent\Services\AgentLiquidityEngine;
use App\Modules\Agent\Services\AgentService;
use App\Modules\Agent\Services\CashInOutService;
use App\Modules\Agent\Services\CommissionService;
use App\Modules\Agent\Services\SettlementService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentService::class);
        $this->app->singleton(CommissionService::class);
        $this->app->singleton(CashInOutService::class);
        $this->app->singleton(SettlementService::class);
        $this->app->singleton(AgentLiquidityEngine::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        Event::listen(CashInCompleted::class, FloatSyncListener::class);
        Event::listen(CashOutCompleted::class, FloatSyncListener::class);
        Event::listen(CashInCompleted::class, CommissionCalculatorListener::class);
        Event::listen(CashOutCompleted::class, CommissionCalculatorListener::class);
    }
}
