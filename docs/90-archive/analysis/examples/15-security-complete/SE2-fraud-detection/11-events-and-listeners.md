# 11 - أحداث الاحتيال (Events & Listeners)

## TransactionFlagged Event

```php
<?php

namespace App\Events;

use App\Models\Transaction;
use App\Services\FraudDetection\FraudResult;
use Illuminate\Foundation\Events\Dispatchable;

class TransactionFlagged
{
    use Dispatchable;

    public function __construct(
        public Transaction $transaction,
        public FraudResult $fraudResult,
    ) {}
}
```

## PinAttemptFailed Event

```php
class PinAttemptFailed
{
    use Dispatchable;

    public function __construct(
        public \App\Models\User $user,
        public int $attempts,
        public string $ip,
    ) {}
}
```

## AccountLocked Event

```php
class AccountLocked
{
    use Dispatchable;

    public function __construct(
        public \App\Models\User $user,
        public string $reason,
        public int $durationMinutes,
    ) {}
}
```

## Event Listeners

```php
// EventServiceProvider
protected $listen = [
    TransactionFlagged::class => [
        SendFraudAlertToAdmin::class,
        NotifyUserOfFlaggedTransaction::class,
    ],
    PinAttemptFailed::class => [
        LogPinAttempt::class,
        CheckPinLockout::class,
    ],
    AccountLocked::class => [
        SendAccountLockedNotification::class,
        LogAccountLock::class,
    ],
];
```

## SendFraudAlertToAdmin Listener

```php
<?php

namespace App\Listeners;

use App\Events\TransactionFlagged;
use App\Notifications\FraudAlertAdminNotification;

class SendFraudAlertToAdmin
{
    public function handle(TransactionFlagged $event): void
    {
        $admins = \App\Models\User::where('is_admin', true)->get();

        foreach ($admins as $admin) {
            $admin->notify(new FraudAlertAdminNotification(
                transaction: $event->transaction,
                fraudResult: $event->fraudResult,
            ));
        }
    }
}
```

## CheckPinLockout Listener

```php
<?php

namespace App\Listeners;

use App\Events\PinAttemptFailed;
use App\Events\AccountLocked;
use Illuminate\Support\Facades\Cache;

class CheckPinLockout
{
    private const MAX_PIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 15; // دقيقة

    public function handle(PinAttemptFailed $event): void
    {
        $cacheKey = 'pin_attempts:' . $event->user->id;
        $attempts = Cache::increment($cacheKey);

        if ($attempts === 1) {
            Cache::put($cacheKey, 1, now()->addMinutes(self::LOCKOUT_DURATION));
        }

        if ($attempts >= self::MAX_PIN_ATTEMPTS) {
            $event->user->update(['status' => 'suspended']);

            event(new AccountLocked(
                user: $event->user,
                reason: 'تجاوز عدد محاولات PIN المسموح بها',
                durationMinutes: self::LOCKOUT_DURATION,
            ));
        }
    }
}
```
