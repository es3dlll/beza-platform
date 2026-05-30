<?php

declare(strict_types=1);

namespace Modules\GovCollections\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\GovCollections\Services\GovCollectionService;

class GovCollectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GovCollectionService::class);
    }
}
