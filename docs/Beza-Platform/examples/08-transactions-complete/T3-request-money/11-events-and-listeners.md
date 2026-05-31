# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: RequestMoneyCompleted

```php
<?php
// app/Events/RequestMoneyCompleted.php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestMoneyCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}
}
```

## Listener: SendRequestMoneyNotification

```php
<?php
// app/Listeners/SendRequestMoneyNotification.php

namespace App\\Listeners;

use App\Events\RequestMoneyCompleted;
use App\Notifications\RequestMoneyNotification;
use Illuminate\ContractsQueueShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendRequestMoneyNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $maxAttempts = 3;

    public function handle(RequestMoneyCompleted $event): void
    {
        $transaction = $event->transaction;

        try {
            $user = $transaction->fromWallet?->user;
            if ($user) {
                $user->notify(new RequestMoneyNotification($transaction));
            }
        } catch (\Throwable $e) {
            Log::error('فشل إرسال الإشعار', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    public function failed(RequestMoneyCompleted $event, \Throwable $exception): void
    {
        Log::critical('فشل إرسال الإشعار بعد 3 محاولات', [
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

protected $listen = [
    RequestMoneyCompleted::class => [
        SendRequestMoneyNotification::class,
    ],
];
```
