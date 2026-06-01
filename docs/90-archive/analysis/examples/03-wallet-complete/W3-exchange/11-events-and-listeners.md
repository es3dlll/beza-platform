# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: ExchangeCompleted

```php
<?php
// app/Events/ExchangeCompleted.php

namespace App\Events;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExchangeCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
        public readonly User       $user,
    ) {}
}
```

## Listener: SendExchangeNotification

```php
<?php
// app/Listeners/SendExchangeNotification.php

namespace App\Listeners;

use App\Events\ExchangeCompleted;
use App\Notifications\ExchangeConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendExchangeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $maxAttempts = 3;
    public $delay = 2;

    public function handle(ExchangeCompleted $event): void
    {
        $transaction = $event->transaction;
        $user = $event->user;

        $metadata = $transaction->metadata;

        try {
            $user->notify(new ExchangeConfirmed(
                transaction: $transaction,
                fromCurrency: $metadata['from_currency'] ?? 'SYP',
                toCurrency: $metadata['to_currency'] ?? 'USD',
                convertedAmount: $metadata['converted_amount'] ?? 0,
                rate: $metadata['rate'] ?? 0,
                fee: (float) $transaction->fee,
                feePercentage: $metadata['fee_percentage'] ?? 0,
            ));
        } catch (\Throwable $e) {
            Log::error('فشل إرسال إشعار الصرافة', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function failed(ExchangeCompleted $event, \Throwable $exception): void
    {
        Log::critical('فشل إرسال إشعار الصرافة بعد 3 محاولات', [
            'transaction_id' => $event->transaction->id,
            'error'          => $exception->getMessage(),
        ]);
    }
}
```

## تسجيل الـ Event & Listener

```php
<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Events\ExchangeCompleted;
use App\Listeners\SendExchangeNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ExchangeCompleted::class => [
            SendExchangeNotification::class,
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
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'database');

// تشغيل العامل
// php artisan queue:work --queue=default --tries=3 --delay=2
```

### لماذا Async؟
| السبب | التفصيل |
|-------|---------|
| سرعة الاستجابة | المستخدم لا ينتظر حتى ترسل الإشعارات |
| Fault tolerance | فشل الإشعار لا يلغي نجاح الصرافة |
| Retry | Queue يعيد المحاولة تلقائياً |
