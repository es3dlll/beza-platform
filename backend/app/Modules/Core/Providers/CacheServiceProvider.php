<?php

declare(strict_types=1);

namespace App\Modules\Core\Providers;

use App\Modules\Core\Services\CacheOrchestrator;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\ServiceProvider;

final class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CacheOrchestrator::class, function ($app) {
            return new CacheOrchestrator(
                cache: $app->make(Repository::class),
            );
        });
    }
}
