<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\OpenFinance\Services\OpenFinanceService;

final class OpenFinanceServiceProvider extends ServiceProvider
{
    public function register(): void { $this->app->singleton(OpenFinanceService::class); }
}
