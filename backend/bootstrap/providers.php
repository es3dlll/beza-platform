<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Modules\Identity\Providers\IdentityServiceProvider::class,
    App\Modules\Ledger\Providers\LedgerServiceProvider::class,
    App\Modules\FinancialCore\Providers\FinancialCoreServiceProvider::class,
    App\Modules\Agent\Providers\AgentServiceProvider::class,
    App\Modules\Fx\Providers\FxServiceProvider::class,
    App\Modules\Fraud\Providers\FraudServiceProvider::class,
    App\Modules\EventBus\Providers\EventBusServiceProvider::class,
    App\Modules\Wallet\Providers\ModuleServiceProvider::class,
    App\Modules\Remittance\Providers\ModuleServiceProvider::class,
    App\Modules\Merchant\Providers\MerchantServiceProvider::class,
    App\Modules\Compliance\Providers\ComplianceServiceProvider::class,
];
