# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: WalletCreated

```php
<?php
// app/Events/WalletCreated.php

namespace App\Events;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User        $user,
        public readonly Wallet      $sypWallet,
        public readonly Wallet      $usdWallet,
        public readonly ?Transaction $bonusTransaction,
    ) {}
}
```

## Listener: SendWelcomeNotification

```php
<?php
// app/Listeners/SendWelcomeNotification.php

namespace App\Listeners;

use App\Events\WalletCreated;
use App\Notifications\WelcomeWithBonus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWelcomeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $maxAttempts = 3;
    public $delay = 5; // 5 ثوانٍ تأخير

    public function handle(WalletCreated $event): void
    {
        $user = $event->user;

        try {
            $user->notify(new WelcomeWithBonus(
                sypWalletNumber: $event->sypWallet->wallet_number,
                usdWalletNumber: $event->usdWallet->wallet_number,
                bonusAmount: 5.00,
            ));
        } catch (\Throwable $e) {
            Log::error('فشل إرسال إشعار الترحيب', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function failed(WalletCreated $event, \Throwable $exception): void
    {
        Log::critical('فشل إرسال إشعار الترحيب بعد 3 محاولات', [
            'user_id' => $event->user->id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
```

## تسجيل الـ Event & Listener

```php
<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Events\WalletCreated;
use App\Listeners\CreateUserWallets;
use App\Listeners\SendWelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            CreateUserWallets::class,
        ],
        WalletCreated::class => [
            SendWelcomeNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
```

## Queue (لتشغيل الـ Listener بشكل Async)

```php
// config/queue.php — استخدام database queue
'default' => env('QUEUE_CONNECTION', 'database');

// تشغيل العامل
// php artisan queue:work --queue=default --tries=3 --delay=5
```

### لماذا Async؟
| السبب | التفصيل |
|-------|---------|
| سرعة الاستجابة | المستخدم لا ينتظر حتى ترسل الإشعارات |
| Fault tolerance | فشل الإشعار لا يلغي نجاح إنشاء المحفظة |
| Retry | Queue يعيد المحاولة تلقائياً |
