# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: UserSuspended

```php
<?php
// app/Events/Admin/UserSuspended.php

namespace App\Events\Admin;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserSuspended
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly int  $suspendedBy,
    ) {}
}
```

## Event: UserActivated

```php
<?php
// app/Events/Admin/UserActivated.php

namespace App\Events\Admin;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserActivated
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
    ) {}
}
```

## Event: UserBlocked

```php
<?php
// app/Events/Admin/UserBlocked.php

namespace App\Events\Admin;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserBlocked
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly int  $blockedBy,
    ) {}
}
```

## Listener: SendUserStatusNotification

```php
<?php
// app/Listeners/Admin/SendUserStatusNotification.php

namespace App\Listeners\Admin;

use App\Events\Admin\UserActivated;
use App\Events\Admin\UserBlocked;
use App\Events\Admin\UserSuspended;
use App\Notifications\UserAccountStatusChanged;
use Illuminate\Events\Dispatcher;

class SendUserStatusNotification
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(UserSuspended::class, [$this, 'handleSuspended']);
        $events->listen(UserActivated::class, [$this, 'handleActivated']);
        $events->listen(UserBlocked::class, [$this, 'handleBlocked']);
    }

    public function handleSuspended(UserSuspended $event): void
    {
        $event->user->notify(new UserAccountStatusChanged(
            status: 'suspended',
            message: 'تم تعليق حسابك مؤقتاً. يرجى التواصل مع الدعم الفني.',
        ));
    }

    public function handleActivated(UserActivated $event): void
    {
        $event->user->notify(new UserAccountStatusChanged(
            status: 'active',
            message: 'تم تفعيل حسابك. يمكنك استخدام المنصة الآن.',
        ));
    }

    public function handleBlocked(UserBlocked $event): void
    {
        $event->user->notify(new UserAccountStatusChanged(
            status: 'blocked',
            message: 'تم حظر حسابك بشكل دائم.',
        ));
    }
}
```

## EventServiceProvider

```php
protected $listen = [
    UserSuspended::class => [],
    UserActivated::class => [],
    UserBlocked::class   => [],
];

protected $subscribe = [
    SendUserStatusNotification::class,
];
```
