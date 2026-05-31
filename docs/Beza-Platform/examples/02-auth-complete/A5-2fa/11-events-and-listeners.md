# 11 - الأحداث والمستمعين — المصادقة الثنائية (2FA)

## Event: TwoFactorEnabled

```php
<?php
// app/Events/TwoFactorEnabled.php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TwoFactorEnabled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}
}
```

## Event: TwoFactorDisabled

```php
<?php
// app/Events/TwoFactorDisabled.php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TwoFactorDisabled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}
}
```

## Listener: LogTwoFactorActivity

```php
<?php
// app/Listeners/LogTwoFactorActivity.php

namespace App\Listeners;

use App\Events\TwoFactorEnabled;
use App\Events\TwoFactorDisabled;
use Illuminate\Support\Facades\Log;

class LogTwoFactorActivity
{
    public function handleEnabled(TwoFactorEnabled $event): void
    {
        Log::info('تفعيل 2FA', [
            'user_id' => $event->user->id,
            'phone'   => $event->user->phone,
            'time'    => now()->toIso8601String(),
        ]);
    }

    public function handleDisabled(TwoFactorDisabled $event): void
    {
        Log::warning('تعطيل 2FA', [
            'user_id' => $event->user->id,
            'phone'   => $event->user->phone,
            'time'    => now()->toIso8601String(),
        ]);
    }

    public function subscribe(\Illuminate\Events\Dispatcher $events): void
    {
        $events->listen(TwoFactorEnabled::class, [self::class, 'handleEnabled']);
        $events->listen(TwoFactorDisabled::class, [self::class, 'handleDisabled']);
    }
}
```

## Listener: SendTwoFactorEmail

```php
<?php
// app/Listeners/SendTwoFactorEmail.php

namespace App\Listeners;

use App\Events\TwoFactorEnabled;
use App\Notifications\TwoFactorActivated;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTwoFactorEmail implements ShouldQueue
{
    public function handle(TwoFactorEnabled $event): void
    {
        // إرسال إشعار للمستخدم بتفعيل 2FA
        $event->user->notify(new TwoFactorActivated());
    }
}
```

## تسجيل الـ Events

```php
<?php
// app/Providers/EventServiceProvider.php

protected $listen = [
    TwoFactorEnabled::class => [
        LogTwoFactorActivity::class,
        SendTwoFactorEmail::class,
    ],
    TwoFactorDisabled::class => [
        LogTwoFactorActivity::class,
    ],
];
```
