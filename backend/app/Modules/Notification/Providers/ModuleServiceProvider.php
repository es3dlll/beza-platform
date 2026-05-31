<?php

declare(strict_types=1);

namespace App\Modules\Notification\Providers;

use App\Modules\Bills\Events\BillPaymentDue;
use App\Modules\Escrow\Events\EscrowDisputed;
use App\Modules\Escrow\Events\EscrowRefunded;
use App\Modules\Escrow\Events\EscrowReleased;
use App\Modules\Fraud\Events\FraudAlertTriggered;
use App\Modules\Ledger\Events\TransferCompleted;
use App\Modules\Notification\Events\NotificationDispatched;
use App\Modules\Notification\Listeners\LogNotificationDispatch;
use App\Modules\Notification\Listeners\SendNotificationOnEvent;
use App\Modules\Notification\Services\NotificationDispatcher;
use App\Modules\Remittance\Events\RemittanceApproved;
use App\Modules\Remittance\Events\RemittanceCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        Event::listen(NotificationDispatched::class, LogNotificationDispatch::class);

        Event::listen(TransferCompleted::class, SendNotificationOnEvent::class);
        Event::listen(RemittanceCompleted::class, SendNotificationOnEvent::class);
        Event::listen(RemittanceApproved::class, SendNotificationOnEvent::class);
        Event::listen(FraudAlertTriggered::class, SendNotificationOnEvent::class);
        Event::listen(BillPaymentDue::class, SendNotificationOnEvent::class);
        Event::listen(EscrowDisputed::class, SendNotificationOnEvent::class);
        Event::listen(EscrowReleased::class, SendNotificationOnEvent::class);
        Event::listen(EscrowRefunded::class, SendNotificationOnEvent::class);
    }

    public function register(): void
    {
        $this->app->singleton(NotificationDispatcher::class);
    }
}
