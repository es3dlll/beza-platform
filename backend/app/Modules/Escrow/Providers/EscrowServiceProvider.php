<?php

declare(strict_types=1);

namespace Modules\Escrow\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Escrow\Services\EscrowService;

final class EscrowServiceProvider extends ServiceProvider
{
    public function register(): void { $this->app->singleton(EscrowService::class); }
}
