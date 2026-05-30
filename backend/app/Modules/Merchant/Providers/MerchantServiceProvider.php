<?php

declare(strict_types=1);

namespace Modules\Merchant\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Merchant\Services\MerchantService;
use Modules\Merchant\Services\MerchantPaymentService;
use Modules\Merchant\Repositories\MerchantRepository;
use Modules\Merchant\Repositories\MerchantStoreRepository;
use Modules\Merchant\Repositories\MerchantPaymentRepository;

final class MerchantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MerchantRepository::class);
        $this->app->singleton(MerchantStoreRepository::class);
        $this->app->singleton(MerchantPaymentRepository::class);
        $this->app->singleton(MerchantService::class);
        $this->app->singleton(MerchantPaymentService::class);
    }
}
