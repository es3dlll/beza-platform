<?php

declare(strict_types=1);

namespace Modules\FX\Providers;

use Modules\FX\Repositories\FxRateRepository;
use Modules\FX\Repositories\FxQuoteRepository;
use Modules\FX\Repositories\FxConversionRepository;
use Modules\FX\Services\FxRateService;
use Modules\FX\Services\FxQuoteService;
use Modules\FX\Services\FxConversionService;
use Illuminate\Support\ServiceProvider;

final class FxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FxRateRepository::class);
        $this->app->singleton(FxQuoteRepository::class);
        $this->app->singleton(FxConversionRepository::class);
        $this->app->singleton(FxRateService::class);
        $this->app->singleton(FxQuoteService::class);
        $this->app->singleton(FxConversionService::class);
    }

    public function boot(): void {}
}
