<?php

namespace Modules\CoreFinancialEngine\Providers;

use Modules\CoreFinancialEngine\Contracts\PostingEngineInterface;
use Modules\CoreFinancialEngine\Contracts\FeeEngineInterface;
use Modules\CoreFinancialEngine\Contracts\HoldEngineInterface;
use Modules\CoreFinancialEngine\Contracts\ReversalEngineInterface;
use Modules\CoreFinancialEngine\Services\PostingEngine;
use Modules\CoreFinancialEngine\Services\FeeEngine;
use Modules\CoreFinancialEngine\Services\HoldEngine;
use Modules\CoreFinancialEngine\Services\ReversalEngine;
use Modules\CoreFinancialEngine\Services\SettlementEngine;
use Illuminate\Support\ServiceProvider;

final class CoreFinancialEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PostingEngineInterface::class, PostingEngine::class);
        $this->app->singleton(FeeEngineInterface::class, FeeEngine::class);
        $this->app->singleton(HoldEngineInterface::class, HoldEngine::class);
        $this->app->singleton(ReversalEngineInterface::class, ReversalEngine::class);
        $this->app->singleton(PostingEngine::class);
        $this->app->singleton(FeeEngine::class);
        $this->app->singleton(HoldEngine::class);
        $this->app->singleton(ReversalEngine::class);
        $this->app->singleton(SettlementEngine::class);
    }

    public function boot(): void {}
}
