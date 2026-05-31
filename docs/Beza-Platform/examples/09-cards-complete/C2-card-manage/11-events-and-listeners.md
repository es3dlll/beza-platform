# 11 - الأحداث والمستمعون (Events & Listeners)

## Event: CardStatusChanged

\\\php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly \,
        public readonly User \,
    ) {}
}
\\\

## Listener: SendCardStatusNotification

\\\php
<?php

namespace App\Listeners;

use App\Events\CardStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendCardStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public \ = 3;

    public function handle(CardStatusChanged \): void
    {
        try {
            // Send notification
            \->user->notify(new Notification(...));
        } catch (\Throwable \CardStatusChanged) {
            Log::error('Notification failed', [
                'user_id' => \->user->id,
                'error' => \CardStatusChanged->getMessage(),
            ]);
        }
    }

    public function failed(CardStatusChanged \, \Throwable \): void
    {
        Log::critical('Listener failed after 3 attempts', [
            'error' => \->getMessage(),
        ]);
    }
}
\\\

## التسجيل (Registration)

\\\php
// app/Providers/EventServiceProvider.php
protected \ = [
    CardStatusChanged::class => [
        SendCardStatusNotification::class,
    ],
];
\\\

## Why Async?

| Reason | Detail |
|--------|--------|
| Response speed | User doesn't wait for notifications |
| Fault tolerance | Notification failure doesn't cancel operation |
| Retry | Queue retries automatically |
| Scalability | Multiple workers can process |
