# 11 - أحداث التدقيق (Events & Listeners)

## Auditable Events

```php
// الاستماع إلى أحداث النظام وتسجيلها
// EventServiceProvider
protected $listen = [
    \App\Events\TransactionCompleted::class => [
        \App\Listeners\Audit\LogTransaction::class,
    ],
    \App\Events\PinChanged::class => [
        \App\Listeners\Audit\LogPinChange::class,
    ],
    \Illuminate\Auth\Events\Login::class => [
        \App\Listeners\Audit\LogLogin::class,
    ],
    \Illuminate\Auth\Events\Failed::class => [
        \App\Listeners\Audit\LogFailedLogin::class,
    ],
];
```

## LogTransaction Listener

```php
<?php

namespace App\Listeners\Audit;

use App\Events\TransactionCompleted;
use App\Services\AuditService;

class LogTransaction
{
    public function handle(TransactionCompleted $event): void
    {
        $txn = $event->transaction;

        app(AuditService::class)->logTransaction(
            eventType: $txn->type . '_created',
            transaction: $txn,
            user: $txn->fromWallet?->user ?? $txn->toWallet?->user,
        );
    }
}
```

## LogLogin Listener

```php
<?php

namespace App\Listeners\Audit;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\Login;

class LogLogin
{
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        app(AuditService::class)->log(
            eventType: 'login',
            loggable: $user,
            user: $user,
            data: [
                'guard' => $event->guard,
                'device_id' => request()->header('X-Device-ID'),
            ],
        );
    }
}
```

## LogFailedLogin Listener

```php
class LogFailedLogin
{
    public function handle(\Illuminate\Auth\Events\Failed $event): void
    {
        $credentials = $event->credentials;
        $phone = $credentials['phone'] ?? 'unknown';

        AuditLog::log(
            eventType: 'login_failed',
            loggable: null,
            user: null,
            data: ['phone' => $phone],
        );
    }
}
```
