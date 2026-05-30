<?php

declare(strict_types=1);

namespace Modules\Remittance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Remittance\Services\CorridorService;
use Modules\Remittance\Services\BeneficiaryService;
use Modules\Remittance\Services\RemittanceService;
use Modules\Remittance\Repositories\CorridorRepository;
use Modules\Remittance\Repositories\BeneficiaryRepository;
use Modules\Remittance\Repositories\RemittanceOrderRepository;

class RemittanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CorridorRepository::class);
        $this->app->singleton(BeneficiaryRepository::class);
        $this->app->singleton(RemittanceOrderRepository::class);

        $this->app->singleton(CorridorService::class);
        $this->app->singleton(BeneficiaryService::class);
        $this->app->singleton(RemittanceService::class);
    }
}
