<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Providers;

use App\Modules\EventBus\Consumers\AuditLogConsumer;
use App\Modules\EventBus\Consumers\ReconciliationConsumer;
use App\Modules\EventBus\Consumers\VelocityUpdateConsumer;
use App\Modules\EventBus\Services\ConsumerRegistry;
use App\Modules\EventBus\Services\EventPublisher;
use App\Modules\EventBus\Services\EventSerializer;
use App\Modules\EventBus\Services\PoisonPillMonitor;
use App\Modules\EventBus\Services\RetryPolicy;
use App\Modules\EventBus\Services\SchemaVersionManager;
use App\Modules\EventBus\Services\EventBusHealthCheck;
use Illuminate\Support\ServiceProvider;

final class EventBusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SchemaVersionManager::class);
        $this->app->singleton(RetryPolicy::class);
        $this->app->singleton(EventSerializer::class);
        $this->app->singleton(PoisonPillMonitor::class);
        $this->app->singleton(ConsumerRegistry::class, function ($app) {
            $registry = new ConsumerRegistry();

            $registry->register(
                'velocity_update',
                $app->make(VelocityUpdateConsumer::class),
                ['financial_core.transaction_posted'],
            );

            $registry->register(
                'audit_log',
                $app->make(AuditLogConsumer::class),
                ['financial_core.#', 'financial.agent.#', 'financial.fx.#', 'financial.fraud.#'],
            );

            $registry->register(
                'reconciliation',
                $app->make(ReconciliationConsumer::class),
                ['ledger.#', 'financial_core.#'],
            );

            return $registry;
        });
        $this->app->singleton(EventPublisher::class);
        $this->app->singleton(EventBusHealthCheck::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        $this->publishes([
            __DIR__ . '/../Config/event_bus.php' => config_path('event_bus.php'),
        ], 'event-bus-config');
    }
}
