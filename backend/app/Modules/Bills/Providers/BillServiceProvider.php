<?php

declare(strict_types=1);

namespace Modules\Bills\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Bills\Services\BillProviderService;
use Modules\Bills\Services\BillPaymentService;
use Modules\Bills\Repositories\BillProviderRepository;
use Modules\Bills\Repositories\BillPaymentRepository;

class BillServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BillProviderRepository::class);
        $this->app->singleton(BillPaymentRepository::class);
        $this->app->singleton(BillProviderService::class);
        $this->app->singleton(BillPaymentService::class);
    }
}
