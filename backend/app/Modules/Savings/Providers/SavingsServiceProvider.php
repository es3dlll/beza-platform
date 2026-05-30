<?php

declare(strict_types=1);

namespace Modules\Savings\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Savings\Services\SavingsService;
use Modules\Savings\Services\ProfitDistributionService;
use Modules\Savings\Services\AutoSweepService;
use Modules\Savings\Repositories\SavingsGoalRepository;
use Modules\Savings\Repositories\SavingsAccountRepository;
use Modules\Savings\Repositories\SavingsTransactionRepository;
use Modules\Savings\Repositories\SavingsProfitRuleRepository;

final class SavingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SavingsGoalRepository::class);
        $this->app->singleton(SavingsAccountRepository::class);
        $this->app->singleton(SavingsTransactionRepository::class);
        $this->app->singleton(SavingsProfitRuleRepository::class);

        $this->app->singleton(ProfitDistributionService::class);
        $this->app->singleton(AutoSweepService::class);
        $this->app->singleton(SavingsService::class);
    }
}
