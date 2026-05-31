<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Providers;

use App\Modules\Ledger\Console\Commands\GenerateOpenApi;
use App\Modules\Ledger\Console\Commands\LedgerReconcile;
use App\Modules\Ledger\Database\Seeders\LedgerSeeder;
use App\Modules\Ledger\Jobs\RetryCBSReportSubmission;
use App\Modules\Ledger\Services\AccountService;
use App\Modules\Ledger\Services\CBSReportGenerator;
use App\Modules\Ledger\Services\CBSReportingService;
use App\Modules\Ledger\Services\HashChainService;
use App\Modules\Ledger\Services\JournalService;
use App\Modules\Ledger\Services\LedgerHealthCheck;
use App\Modules\Ledger\Services\NotificationService;
use App\Modules\Ledger\Services\ReconciliationService;
use Illuminate\Support\ServiceProvider;

final class LedgerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HashChainService::class);
        $this->app->singleton(AccountService::class);
        $this->app->singleton(JournalService::class);
        $this->app->singleton(CBSReportGenerator::class);
        $this->app->singleton(ReconciliationService::class);
        $this->app->singleton(CBSReportingService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(LedgerHealthCheck::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Database/Migrations' => database_path('migrations'),
            ], 'ledger-migrations');

            $this->publishes([
                __DIR__ . '/../Database/Seeders' => database_path('seeders'),
            ], 'ledger-seeders');

            $this->commands([
                LedgerReconcile::class,
                GenerateOpenApi::class,
            ]);
        }
    }
}
