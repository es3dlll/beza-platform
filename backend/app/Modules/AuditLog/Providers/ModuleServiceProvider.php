<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Providers;

use App\Modules\AuditLog\Listeners\LogWalletTransfer;
use App\Modules\Ledger\Events\TransferCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        Event::listen(TransferCompleted::class, LogWalletTransfer::class);
    }
}
