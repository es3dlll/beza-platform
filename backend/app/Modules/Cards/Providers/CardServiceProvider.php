<?php

declare(strict_types=1);

namespace Modules\Cards\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Cards\Services\CardService;
use Modules\Cards\Services\CardAuthorizationService;
use Modules\Cards\Services\CardSpendingControlService;
use Modules\Cards\Repositories\CardRepository;
use Modules\Cards\Repositories\CardTransactionRepository;
use Modules\Cards\Repositories\CardMerchantBlockRepository;

final class CardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CardRepository::class);
        $this->app->singleton(CardTransactionRepository::class);
        $this->app->singleton(CardMerchantBlockRepository::class);

        $this->app->singleton(CardSpendingControlService::class);
        $this->app->singleton(CardAuthorizationService::class);
        $this->app->singleton(CardService::class);
    }
}
