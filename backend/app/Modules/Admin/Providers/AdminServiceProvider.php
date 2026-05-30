<?php

declare(strict_types=1);

namespace Modules\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Admin\Services\EducationAdminService;
use Modules\Admin\Services\EscrowAdminService;
use Modules\Admin\Services\FinancingAdminService;
use Modules\Admin\Services\HumanitarianAdminService;
use Modules\Admin\Services\InvestmentsAdminService;
use Modules\Admin\Services\MarketplaceAdminService;
use Modules\Admin\Services\OpenFinanceAdminService;
use Modules\Admin\Services\TakafulAdminService;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FinancingAdminService::class);
        $this->app->singleton(EducationAdminService::class);
        $this->app->singleton(HumanitarianAdminService::class);
        $this->app->singleton(OpenFinanceAdminService::class);
        $this->app->singleton(MarketplaceAdminService::class);
        $this->app->singleton(EscrowAdminService::class);
        $this->app->singleton(TakafulAdminService::class);
        $this->app->singleton(InvestmentsAdminService::class);
    }
}
