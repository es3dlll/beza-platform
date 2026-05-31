<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Providers;

use App\Modules\FinancialCore\Services\Engines\FeeEngine;
use App\Modules\FinancialCore\Services\Engines\HoldEngine;
use App\Modules\FinancialCore\Services\Engines\PostingEngine;
use App\Modules\FinancialCore\Services\Engines\ReversalEngine;
use App\Modules\FinancialCore\Services\IdempotencyService;
use App\Modules\FinancialCore\Services\TransactionService;
use Illuminate\Support\ServiceProvider;

final class FinancialCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IdempotencyService::class);
        $this->app->singleton(HoldEngine::class);
        $this->app->singleton(PostingEngine::class);
        $this->app->singleton(FeeEngine::class);
        $this->app->singleton(ReversalEngine::class);
        $this->app->singleton(TransactionService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
