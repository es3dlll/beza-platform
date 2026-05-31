# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: DealCompleted

```php
<?php
// app/Events/DealCompleted.php

namespace App\Events;

use App\Models\Deal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DealCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Deal $deal,
        public readonly array $distributions,
    ) {}
}
```

## Listener: SendProfitNotification

```php
<?php
// app/Listeners/SendProfitNotification.php

namespace App\Listeners;

use App\Events\DealCompleted;
use App\Notifications\ProfitReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendProfitNotification implements ShouldQueue
{
    public function handle(DealCompleted $event): void
    {
        foreach ($event->distributions as $dist) {
            try {
                $user = \App\Models\User::find($dist['investor_id']);
                if ($user) {
                    $user->notify(new ProfitReceived(
                        dealTitle: $event->deal->title,
                        amount:    $dist['profit_share'],
                        currency:  $event->deal->currency,
                    ));
                }
            } catch (\Throwable $e) {
                Log::warning('فشل إشعار أرباح', [
                    'investor_id' => $dist['investor_id'],
                ]);
            }
        }
    }
}
```
