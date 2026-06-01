# 11 - الأحداث والمستمعون (Events & Listeners)

## Event: UserRegistered

```php
<?php
// app/Events/UserRegistered.php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}
}
```

## Listener: CreateUserWallets

```php
<?php
// app/Listeners/CreateUserWallets.php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\Wallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateUserWallets implements ShouldQueue
{
    public $maxAttempts = 3;

    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        foreach (['SYP', 'USD'] as $currency) {
            Wallet::create([
                'user_id'       => $user->id,
                'currency'      => $currency,
                'wallet_number' => $this->generateNumber($currency),
                'balance'       => $currency === 'USD' ? 5.00 : 0.00,
                'frozen_balance'=> 0.00,
                'is_active'     => true,
            ]);
        }

        Log::info('تم إنشاء محافظ المستخدم', ['user_id' => $user->id]);
    }

    private function generateNumber(string $currency): string
    {
        $prefix = $currency === 'SYP' ? '62' : '63';
        do {
            $number = $prefix . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (Wallet::where('wallet_number', $number)->exists());

        return $number;
    }
}
```

## Listener: SendWelcomeNotification

```php
<?php
// app/Listeners/SendWelcomeNotification.php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Notifications\WelcomeSms;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendWelcomeNotification implements ShouldQueue
{
    public $maxAttempts = 3;

    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        try {
            $user->notify(new WelcomeSms($user));
        } catch (\Throwable $e) {
            Log::error('فشل إرسال رسالة الترحيب', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function failed(UserRegistered $event, \Throwable $e): void
    {
        Log::critical('فشل إرسال الترحيب بعد 3 محاولات', [
            'user_id' => $event->user->id,
            'error'   => $e->getMessage(),
        ]);
    }
}
```

## تسجيل الـ Event & Listener

```php
<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Events\UserRegistered;
use App\Listeners\CreateUserWallets;
use App\Listeners\SendWelcomeNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegistered::class => [
            CreateUserWallets::class,
            SendWelcomeNotification::class,
        ],
    ];
}
```
