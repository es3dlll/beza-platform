<?php

declare(strict_types=1);

namespace Modules\Float\Providers;

use Modules\Float\Repositories\FloatRepository;
use Modules\Float\Services\FloatService;
use Modules\Float\Services\FloatOrchestrator;
use Illuminate\Support\ServiceProvider;

final class FloatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FloatRepository::class);
        $this->app->singleton(FloatService::class);
        $this->app->singleton(FloatOrchestrator::class);
    }

    public function boot(): void {}
}
