# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: QRTransferCompleted

```php
<?php
// app/Events/QRTransferCompleted.php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QRTransferCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}
}
```

## Listener: SendQRTransferNotification

```php
<?php
// app/Listeners/SendQRTransferNotification.php

namespace App\\Listeners;

use App\Events\QRTransferCompleted;
use App\Notifications\QRTransferNotification;
use Illuminate\ContractsQueueShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendQRTransferNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $maxAttempts = 3;

    public function handle(QRTransferCompleted $event): void
    {
        $transaction = $event->transaction;

        try {
            $user = $transaction->fromWallet?->user;
            if ($user) {
                $user->notify(new QRTransferNotification($transaction));
            }
        } catch (\Throwable $e) {
            Log::error('فشل إرسال الإشعار', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    public function failed(QRTransferCompleted $event, \Throwable $exception): void
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
    QRTransferCompleted::class => [
        SendQRTransferNotification::class,
    ],
];
```
