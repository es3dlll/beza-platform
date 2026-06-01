# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: AgentDepositCompleted

```php
<?php
// app/Events/AgentDepositCompleted.php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentDepositCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}
}
```

## Listener: SendAgentDepositNotification

```php
<?php
// app/Listeners/SendAgentDepositNotification.php

namespace App\\Listeners;

use App\Events\AgentDepositCompleted;
use App\Notifications\AgentDepositNotification;
use Illuminate\ContractsQueueShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendAgentDepositNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $maxAttempts = 3;

    public function handle(AgentDepositCompleted $event): void
    {
        $transaction = $event->transaction;

        try {
            $user = $transaction->fromWallet?->user;
            if ($user) {
                $user->notify(new AgentDepositNotification($transaction));
            }
        } catch (\Throwable $e) {
            Log::error('فشل إرسال الإشعار', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    public function failed(AgentDepositCompleted $event, \Throwable $exception): void
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
    AgentDepositCompleted::class => [
        SendAgentDepositNotification::class,
    ],
];
```
