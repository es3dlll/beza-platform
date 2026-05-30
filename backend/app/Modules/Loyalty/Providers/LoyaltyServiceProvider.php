<?php

declare(strict_types=1);

namespace Modules\Loyalty\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Loyalty\Services\PointsService;
use Modules\Loyalty\Services\CashbackService;
use Modules\Loyalty\Services\TierService;
use Modules\Loyalty\Services\RewardService;
use Modules\Loyalty\Repositories\LoyaltyTierRepository;
use Modules\Loyalty\Repositories\LoyaltyPointsRepository;
use Modules\Loyalty\Repositories\LoyaltyPointsTransactionRepository;
use Modules\Loyalty\Repositories\CashbackRuleRepository;
use Modules\Loyalty\Repositories\LoyaltyRewardRepository;

class LoyaltyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoyaltyTierRepository::class);
        $this->app->singleton(LoyaltyPointsRepository::class);
        $this->app->singleton(LoyaltyPointsTransactionRepository::class);
        $this->app->singleton(CashbackRuleRepository::class);
        $this->app->singleton(LoyaltyRewardRepository::class);

        $this->app->singleton(TierService::class);
        $this->app->singleton(PointsService::class);
        $this->app->singleton(CashbackService::class);
        $this->app->singleton(RewardService::class);
    }
}
