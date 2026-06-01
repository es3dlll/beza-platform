# 11 - الأحداث والمستمعون (Events & Listeners)

## Event: CardEnrolledInWallet

```php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardEnrolledInWallet
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly mixed $enrollment,
        public readonly User $user,
    ) {}
}
```

## Listener: SendWalletEnrollNotification

```php
<?php

namespace App\Listeners;

use App\Events\CardEnrolledInWallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWalletEnrollNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(CardEnrolledInWallet $event): void
    {
        try {
            $event->user->notify(new \App\Notifications\WalletEnrolledNotification($event->enrollment));
        } catch (\Throwable $e) {
            Log::error('Notification failed', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(CardEnrolledInWallet $event, \Throwable $e): void
    {
        Log::critical('Listener failed after 3 attempts', [
            'error' => $e->getMessage(),
        ]);
    }
}
```

## التسجيل (Registration)

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    CardEnrolledInWallet::class => [
        SendWalletEnrollNotification::class,
    ],
];
```

## Why Async?

| Reason | Detail |
|--------|--------|
| Response speed | User doesn't wait for notifications |
| Fault tolerance | Notification failure doesn't cancel operation |
| Retry | Queue retries automatically |
| Scalability | Multiple workers can process |
