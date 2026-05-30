<?php

declare(strict_types=1);

namespace Modules\Takaful\Providers;

use Modules\Takaful\Services\TakafulService;
use Illuminate\Support\ServiceProvider;

final class TakafulServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TakafulService::class);
    }

    public function boot(): void {}
}
