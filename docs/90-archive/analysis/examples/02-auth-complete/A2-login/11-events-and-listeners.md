# 11 - الأحداث والمستمعون (Events & Listeners)

## Event: UserLoggedIn

```php
<?php
// app/Events/UserLoggedIn.php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User   $user,
        public readonly string $ip,
        public readonly ?string $deviceId,
    ) {}
}
```

## Listener: LogSuccessfulLogin

```php
<?php
// app/Listeners/LogSuccessfulLogin.php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin implements ShouldQueue
{
    public function handle(UserLoggedIn $event): void
    {
        Log::info('تسجيل دخول ناجح', [
            'user_id'   => $event->user->id,
            'phone'     => $event->user->phone,
            'ip'        => $event->ip,
            'device_id' => $event->deviceId,
            'time'      => now()->toIso8601String(),
        ]);
    }

    public function failed(UserLoggedIn $event, \Throwable $e): void
    {
        Log::critical('فشل تسجيل حدث تسجيل الدخول', [
            'user_id' => $event->user->id,
            'error'   => $e->getMessage(),
        ]);
    }
}
```

## Listener: SendLoginAlert (اختياري)

```php
<?php
// app/Listeners/SendLoginAlert.php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Notifications\LoginAlert;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLoginAlert implements ShouldQueue
{
    public function handle(UserLoggedIn $event): void
    {
        // إرسال إشعار للمستخدم إذا كان الجهاز جديداً
        if ($event->deviceId && $event->user->device_id !== $event->deviceId) {
            $event->user->notify(new LoginAlert(
                ip: $event->ip,
                deviceId: $event->deviceId,
            ));
        }
    }
}
```

## تسجيل الـ Events

```php
<?php
// app/Providers/EventServiceProvider.php

protected $listen = [
    UserLoggedIn::class => [
        LogSuccessfulLogin::class,
        // SendLoginAlert::class, // اختياري
    ],
];
```
