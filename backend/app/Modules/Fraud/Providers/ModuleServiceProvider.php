<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Providers;

use App\Modules\Agent\Events\LiquidityRequested;
use App\Modules\Fraud\Events\LiquidityApproved;
use App\Modules\Fraud\Events\LiquidityCompleted;
use App\Modules\Fraud\Listeners\LogFraudResult;
use App\Modules\Fraud\Listeners\ProcessLiquidityApproval;
use App\Modules\Fraud\Listeners\RunFraudDetection;
use App\Modules\Fraud\Services\ComplianceRuleManager;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

final class ModuleServiceProvider extends EventServiceProvider
{
    protected $listen = [
        LiquidityRequested::class => [RunFraudDetection::class],
        LiquidityApproved::class => [
            ProcessLiquidityApproval::class,
            LogFraudResult::class,
        ],
        LiquidityCompleted::class => [LogFraudResult::class],
    ];

    public function boot(): void
    {
        parent::boot();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }

    public function register(): void
    {
        $this->app->singleton(ComplianceRuleManager::class);
    }
}
