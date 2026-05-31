<?php

declare(strict_types=1);

use App\Modules\Agent\Providers\ModuleServiceProvider as AgentProvider;
use App\Modules\Analytics\Providers\ModuleServiceProvider as AnalyticsProvider;
use App\Modules\AuditLog\Providers\ModuleServiceProvider as AuditLogProvider;
use App\Modules\BillProvider\Providers\ModuleServiceProvider as BillProviderProvider;
use App\Modules\Bills\Providers\ModuleServiceProvider as BillsProvider;
use App\Modules\Core\Providers\ModuleServiceProvider as CoreProvider;
use App\Modules\Escrow\Providers\ModuleServiceProvider as EscrowProvider;
use App\Modules\Marketplace\Providers\ModuleServiceProvider as MarketplaceProvider;
use App\Modules\Fraud\Providers\ModuleServiceProvider as FraudProvider;
use App\Modules\FX\Providers\ModuleServiceProvider as FXProvider;
use App\Modules\Identity\Providers\ModuleServiceProvider as IdentityProvider;
use App\Modules\Notification\Providers\ModuleServiceProvider as NotificationProvider;
use App\Modules\Ledger\Providers\ModuleServiceProvider as LedgerProvider;
use App\Modules\Remittance\Providers\ModuleServiceProvider as RemittanceProvider;
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
    FraudProvider::class,
    FXProvider::class,
    RemittanceProvider::class,
    BillProviderProvider::class,
    BillsProvider::class,
    MarketplaceProvider::class,
    EscrowProvider::class,
    NotificationProvider::class,
    AnalyticsProvider::class,
];
