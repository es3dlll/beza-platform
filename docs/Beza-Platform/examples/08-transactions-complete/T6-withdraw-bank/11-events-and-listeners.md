# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: BankWithdrawalCompleted

```php
<?php
// app/Events/BankWithdrawalCompleted.php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BankWithdrawalCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}
}
```

## Listener: SendBankWithdrawalNotification

```php
<?php
// app/Listeners/SendBankWithdrawalNotification.php

namespace App\\Listeners;

use App\Events\BankWithdrawalCompleted;
use App\Notifications\BankWithdrawalNotification;
use Illuminate\ContractsQueueShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendBankWithdrawalNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $maxAttempts = 3;

    public function handle(BankWithdrawalCompleted $event): void
    {
        $transaction = $event->transaction;

        try {
            $user = $transaction->fromWallet?->user;
            if ($user) {
                $user->notify(new BankWithdrawalNotification($transaction));
            }
        } catch (\Throwable $e) {
            Log::error('فشل إرسال الإشعار', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    public function failed(BankWithdrawalCompleted $event, \Throwable $exception): void
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
    BankWithdrawalCompleted::class => [
        SendBankWithdrawalNotification::class,
    ],
];
```
