# 11 - أحداث 2FA (Events & Listeners)

## TwoFactorEnabled Event

```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class TwoFactorEnabled
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public User $user,
        public array $recoveryCodes
    ) {}
}
```

## TwoFactorVerified Event

```php
class TwoFactorVerified
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public User $user,
        public string $method, // 'totp' or 'recovery_code'
    ) {}
}
```

## TwoFactorDisabled Event

```php
class TwoFactorDisabled
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public User $user) {}
}
```

## Listeners

```php
// EventServiceProvider
protected $listen = [
    TwoFactorEnabled::class => [
        SendTwoFactorEnabledNotification::class,
        LogTwoFactorEnabled::class,
    ],
    TwoFactorVerified::class => [
        LogTwoFactorVerification::class,
    ],
    TwoFactorDisabled::class => [
        SendTwoFactorDisabledNotification::class,
        LogTwoFactorDisabled::class,
    ],
];
```

## LogTwoFactorEnabled Listener

```php
<?php

namespace App\Listeners;

use App\Events\TwoFactorEnabled;
use Illuminate\Support\Facades\Log;

class LogTwoFactorEnabled
{
    public function handle(TwoFactorEnabled $event): void
    {
        activity()
            ->performedOn($event->user)
            ->causedBy($event->user)
            ->withProperties(['ip' => request()->ip()])
            ->log('تم تفعيل المصادقة الثنائية');
    }
}
```
