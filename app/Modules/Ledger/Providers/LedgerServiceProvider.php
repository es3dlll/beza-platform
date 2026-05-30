<?php
declare(strict_types=1);

namespace Modules\Ledger\Providers;

use Modules\Ledger\Repositories\LedgerAccountRepository;
use Modules\Ledger\Repositories\JournalEntryRepository;
use Modules\Ledger\Repositories\LedgerHoldRepository;
use Modules\Ledger\Services\AccountService;
use Modules\Ledger\Services\JournalService;
use Modules\Ledger\Services\HoldService;
use Modules\Ledger\Services\TrialBalanceService;
use Illuminate\Support\ServiceProvider;

final class LedgerServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->app->singleton(LedgerAccountRepository::class);
        $this->app->singleton(JournalEntryRepository::class);
        $this->app->singleton(LedgerHoldRepository::class);
        $this->app->singleton(AccountService::class);
        $this->app->singleton(JournalService::class);
        $this->app->singleton(HoldService::class);
        $this->app->singleton(TrialBalanceService::class);
    }
}
