<?php

declare(strict_types=1);

namespace Modules\Agent\Providers;

use Modules\Agent\Contracts\AgentServiceInterface;
use Modules\Agent\Services\AgentService;
use Modules\Agent\Repositories\AgentRepository;
use Illuminate\Support\ServiceProvider;

final class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentRepository::class);
        $this->app->singleton(AgentServiceInterface::class, AgentService::class);
        $this->app->singleton(AgentService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
