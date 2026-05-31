<?php

declare(strict_types=1);

namespace App\Modules\Bills\Providers;

use App\Modules\Bills\Events\BillPaymentCompleted;
use App\Modules\Bills\Events\BillPaymentFailed;
use App\Modules\Bills\Events\BillPaymentInitiated;
use App\Modules\Bills\Events\BillScheduleConfirmed;
use App\Modules\Bills\Events\BillScheduled;
use App\Modules\Bills\Listeners\LogBillActivity;
use App\Modules\Bills\Listeners\RunFraudCheckOnBillPayment;
use App\Modules\Bills\Listeners\UpdateScheduledPaymentAfterCompletion;
use App\Modules\Bills\Services\BillPaymentProcessor;
use App\Modules\Bills\Services\BillPaymentScheduler;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        Event::listen(BillPaymentInitiated::class, RunFraudCheckOnBillPayment::class);
        Event::listen(BillPaymentInitiated::class, LogBillActivity::class);
        Event::listen(BillPaymentCompleted::class, UpdateScheduledPaymentAfterCompletion::class);
        Event::listen(BillPaymentCompleted::class, LogBillActivity::class);
        Event::listen(BillPaymentFailed::class, LogBillActivity::class);
        Event::listen(BillScheduled::class, LogBillActivity::class);
        Event::listen(BillScheduleConfirmed::class, LogBillActivity::class);
    }

    public function register(): void
    {
        $this->app->singleton(BillPaymentProcessor::class);
        $this->app->singleton(BillPaymentScheduler::class);
    }
}
