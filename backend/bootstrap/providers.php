<?php

declare(strict_types=1);

use App\Modules\Agent\Providers\ModuleServiceProvider as AgentProvider;
use App\Modules\AuditLog\Providers\ModuleServiceProvider as AuditLogProvider;
use App\Modules\Core\Providers\ModuleServiceProvider as CoreProvider;
use App\Modules\Identity\Providers\ModuleServiceProvider as IdentityProvider;
use App\Modules\Ledger\Providers\ModuleServiceProvider as LedgerProvider;
use App\Modules\Wallet\Providers\ModuleServiceProvider as WalletProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    CoreProvider::class,
    IdentityProvider::class,
    WalletProvider::class,
    LedgerProvider::class,
    AuditLogProvider::class,
    AgentProvider::class,
];
