<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Humanitarian\Services\HumanitarianService;

final class HumanitarianServiceProvider extends ServiceProvider
{
    public function register(): void { $this->app->singleton(HumanitarianService::class); }
}
