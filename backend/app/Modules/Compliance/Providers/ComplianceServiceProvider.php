<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Providers;

use App\Modules\Compliance\Events\AutoBlockTriggered;
use App\Modules\Compliance\Events\TransactionCompleted;
use App\Modules\Compliance\Listeners\AutoBlockListener;
use App\Modules\Compliance\Listeners\CaseEscalationListener;
use App\Modules\Compliance\Listeners\SanctionListUpdaterListener;
use App\Modules\Compliance\Listeners\TransactionMonitorListener;
use App\Modules\Compliance\Services\FraudDetectionEngine;
use App\Modules\Compliance\Services\RuleEngine;
use App\Modules\Compliance\Services\SanctionService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class ComplianceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FraudDetectionEngine::class);
        $this->app->singleton(RuleEngine::class);
        $this->app->singleton(SanctionService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        Event::listen(TransactionCompleted::class, TransactionMonitorListener::class);
        Event::listen(TransactionCompleted::class, SanctionListUpdaterListener::class);
        Event::listen(AutoBlockTriggered::class, AutoBlockListener::class);
    }
}
