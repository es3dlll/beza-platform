<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Providers;

use App\Modules\Remittance\Events\RemittanceApproved;
use App\Modules\Remittance\Events\RemittanceCompleted;
use App\Modules\Remittance\Events\RemittanceInitiated;
use App\Modules\Remittance\Listeners\ExecuteRemittanceTransfer;
use App\Modules\Remittance\Listeners\LogRemittanceActivity;
use App\Modules\Remittance\Listeners\RunFraudCheckOnRemittance;
use App\Modules\Remittance\Services\RemittanceFeeCalculator;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

final class ModuleServiceProvider extends EventServiceProvider
{
    protected $listen = [
        RemittanceInitiated::class => [
            RunFraudCheckOnRemittance::class,
            LogRemittanceActivity::class,
        ],
        RemittanceApproved::class => [
            ExecuteRemittanceTransfer::class,
            LogRemittanceActivity::class,
        ],
        RemittanceCompleted::class => [LogRemittanceActivity::class],
    ];

    public function boot(): void
    {
        parent::boot();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }

    public function register(): void
    {
        $this->app->singleton(RemittanceFeeCalculator::class);
    }
}
