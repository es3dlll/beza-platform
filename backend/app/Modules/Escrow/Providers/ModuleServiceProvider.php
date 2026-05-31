<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Providers;

use App\Modules\Escrow\Events\EscrowDisputed;
use App\Modules\Escrow\Events\EscrowFunded;
use App\Modules\Escrow\Events\EscrowInitiated;
use App\Modules\Escrow\Events\EscrowRefunded;
use App\Modules\Escrow\Events\EscrowReleased;
use App\Modules\Escrow\Listeners\LogEscrowDisputed;
use App\Modules\Escrow\Listeners\LogEscrowFunded;
use App\Modules\Escrow\Listeners\LogEscrowInitiated;
use App\Modules\Escrow\Listeners\LogEscrowRefunded;
use App\Modules\Escrow\Listeners\LogEscrowReleased;
use App\Modules\Escrow\Listeners\RunFraudCheckOnEscrow;
use App\Modules\Escrow\Services\EscrowCustodianService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        Event::listen(EscrowInitiated::class, RunFraudCheckOnEscrow::class);
        Event::listen(EscrowInitiated::class, LogEscrowInitiated::class);
        Event::listen(EscrowFunded::class, LogEscrowFunded::class);
        Event::listen(EscrowReleased::class, LogEscrowReleased::class);
        Event::listen(EscrowRefunded::class, LogEscrowRefunded::class);
        Event::listen(EscrowDisputed::class, LogEscrowDisputed::class);
    }

    public function register(): void
    {
        $this->app->singleton(EscrowCustodianService::class);
    }
}
