<?php

declare(strict_types=1);

namespace Modules\Wallet\Providers;

use Modules\Wallet\Contracts\LedgerAclInterface;
use Modules\Wallet\Contracts\WalletServiceInterface;
use Modules\Wallet\Services\LedgerAclService;
use Modules\Wallet\Services\WalletService;
use Modules\Wallet\Repositories\WalletRepository;
use Illuminate\Support\ServiceProvider;

final class WalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WalletRepository::class);
        $this->app->singleton(LedgerAclInterface::class, LedgerAclService::class);
        $this->app->singleton(WalletServiceInterface::class, WalletService::class);
        $this->app->singleton(WalletService::class);
    }

    public function boot(): void {}
}
