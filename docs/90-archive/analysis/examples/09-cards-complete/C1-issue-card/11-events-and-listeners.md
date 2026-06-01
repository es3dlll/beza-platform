# 11 - الأحداث والمستمعون (Events & Listeners)

## Event: CardIssued

\\\php
<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardIssued
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly \,
        public readonly User \,
    ) {}
}
\\\

## Listener: SendCardNotification

\\\php
<?php

namespace App\Listeners;

use App\Events\CardIssued;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendCardNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public \ = 3;

    public function handle(CardIssued \): void
    {
        try {
            // Send notification
            \->user->notify(new Notification(...));
        } catch (\Throwable \CardIssued) {
            Log::error('Notification failed', [
                'user_id' => \->user->id,
                'error' => \CardIssued->getMessage(),
            ]);
        }
    }

    public function failed(CardIssued \, \Throwable \): void
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
    CardIssued::class => [
        SendCardNotification::class,
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
