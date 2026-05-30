<?php

declare(strict_types=1);

namespace Modules\Financing\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Financing\Services\FinancingService;

class FinancingServiceProvider extends ServiceProvider
{
    public function register(): void { $this->app->singleton(FinancingService::class); }
}
