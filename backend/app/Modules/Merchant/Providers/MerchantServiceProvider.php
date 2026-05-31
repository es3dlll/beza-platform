<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Providers;

use App\Modules\Merchant\Listeners\DailySettlementListener;
use App\Modules\Merchant\Listeners\FraudPatternListener;
use App\Modules\Merchant\Listeners\QRValidationListener;
use App\Modules\Merchant\Listeners\TaxComplianceListener;
use App\Modules\Merchant\Services\MerchantEngine;
use App\Modules\Merchant\Services\QRService;
use App\Modules\Merchant\Services\TaxService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class MerchantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MerchantEngine::class);
        $this->app->singleton(QRService::class);
        $this->app->singleton(TaxService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        Event::listen(
            \App\Modules\Merchant\Events\InvoicePaymentInitiated::class,
            QRValidationListener::class,
        );
        Event::listen(
            \App\Modules\Merchant\Events\InvoicePaid::class,
            TaxComplianceListener::class,
        );
        Event::listen(
            \App\Modules\Merchant\Events\TriggerMerchantSettlement::class,
            DailySettlementListener::class,
        );
        Event::listen(
            \App\Modules\Merchant\Events\InvoicePaymentInitiated::class,
            FraudPatternListener::class,
        );
    }
}
