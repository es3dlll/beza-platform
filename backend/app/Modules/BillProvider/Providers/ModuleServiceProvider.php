<?php

declare(strict_types=1);

namespace App\Modules\BillProvider\Providers;

use App\Modules\BillProvider\Events\BillProviderDeactivated;
use App\Modules\BillProvider\Events\BillProviderRegistered;
use App\Modules\BillProvider\Listeners\LogBillProviderActivity;
use App\Modules\BillProvider\Services\BillProviderCatalogService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        Event::listen(BillProviderRegistered::class, LogBillProviderActivity::class);
        Event::listen(BillProviderDeactivated::class, LogBillProviderActivity::class);
    }

    public function register(): void
    {
        $this->app->singleton(BillProviderCatalogService::class);
    }
}
