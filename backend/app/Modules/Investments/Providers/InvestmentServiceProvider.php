<?php

declare(strict_types=1);

namespace Modules\Investments\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Investments\Services\InvestmentService;

final class InvestmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InvestmentService::class);
    }

    public function boot(): void {}
}
