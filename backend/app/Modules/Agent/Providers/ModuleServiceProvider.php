<?php

declare(strict_types=1);

namespace App\Modules\Agent\Providers;

use App\Modules\Agent\Events\AgentRegistered;
use App\Modules\Agent\Events\CommissionCalculated;
use App\Modules\Agent\Events\LiquidityRequested;
use App\Modules\Agent\Listeners\LogAgentRegistered;
use App\Modules\Agent\Listeners\LogCommissionCalculated;
use App\Modules\Agent\Listeners\LogLiquidityRequested;
use App\Modules\Agent\Services\AgentCommissionCalculator;
use App\Modules\Agent\Services\LiquidityPoolService;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

final class ModuleServiceProvider extends EventServiceProvider
{
    protected $listen = [
        AgentRegistered::class => [LogAgentRegistered::class],
        LiquidityRequested::class => [LogLiquidityRequested::class],
        CommissionCalculated::class => [LogCommissionCalculated::class],
    ];

    public function boot(): void
    {
        parent::boot();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }

    public function register(): void
    {
        $this->app->singleton(LiquidityPoolService::class);
        $this->app->singleton(AgentCommissionCalculator::class);
    }
}
