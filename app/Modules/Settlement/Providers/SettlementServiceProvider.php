<?php

declare(strict_types=1);

namespace Modules\Settlement\Providers;

use Modules\Settlement\Repositories\SettlementRepository;
use Modules\Settlement\Services\SettlementService;
use Modules\Settlement\Services\AgentSettlementService;
use Illuminate\Support\ServiceProvider;

final class SettlementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettlementRepository::class);
        $this->app->singleton(SettlementService::class);
        $this->app->singleton(AgentSettlementService::class);
    }

    public function boot(): void {}
}
