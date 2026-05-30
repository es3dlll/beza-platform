<?php

declare(strict_types=1);

namespace Modules\Marketplace\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Marketplace\Services\CatalogService;
use Modules\Marketplace\Services\GiftCardService;
use Modules\Marketplace\Services\LoyaltyService;
use Modules\Marketplace\Services\OrderService;
use Modules\Marketplace\Services\PromoService;
use Modules\Marketplace\Services\VendorService;

final class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CatalogService::class);
        $this->app->singleton(GiftCardService::class);
        $this->app->singleton(LoyaltyService::class);
        $this->app->singleton(OrderService::class);
        $this->app->singleton(PromoService::class);
        $this->app->singleton(VendorService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'marketplace');
    }
}
