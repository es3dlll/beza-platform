<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Providers;

use App\Modules\Analytics\Listeners\AggregateOnEvent;
use App\Modules\Analytics\Services\AnalyticsAggregator;
use App\Modules\Analytics\Services\ReportExporter;
use App\Modules\Bills\Events\BillPaymentCompleted;
use App\Modules\Escrow\Events\EscrowFunded;
use App\Modules\Escrow\Events\EscrowReleased;
use App\Modules\Remittance\Events\RemittanceCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        Event::listen(RemittanceCompleted::class, AggregateOnEvent::class);
        Event::listen(BillPaymentCompleted::class, AggregateOnEvent::class);
        Event::listen(EscrowFunded::class, AggregateOnEvent::class);
        Event::listen(EscrowReleased::class, AggregateOnEvent::class);
    }

    public function register(): void
    {
        $this->app->singleton(AnalyticsAggregator::class);
        $this->app->singleton(ReportExporter::class);
    }
}
