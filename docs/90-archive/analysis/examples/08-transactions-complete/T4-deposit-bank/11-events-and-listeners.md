# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: BankDepositCompleted

```php
<?php
// app/Events/BankDepositCompleted.php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BankDepositCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}
}
```

## Listener: SendBankDepositNotification

```php
<?php
// app/Listeners/SendBankDepositNotification.php

namespace App\\Listeners;

use App\Events\BankDepositCompleted;
use App\Notifications\BankDepositNotification;
use Illuminate\ContractsQueueShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendBankDepositNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $maxAttempts = 3;

    public function handle(BankDepositCompleted $event): void
    {
        $transaction = $event->transaction;

        try {
            $user = $transaction->fromWallet?->user;
            if ($user) {
                $user->notify(new BankDepositNotification($transaction));
            }
        } catch (\Throwable $e) {
            Log::error('فشل إرسال الإشعار', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    public function failed(BankDepositCompleted $event, \Throwable $exception): void
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
    BankDepositCompleted::class => [
        SendBankDepositNotification::class,
    ],
];
```
