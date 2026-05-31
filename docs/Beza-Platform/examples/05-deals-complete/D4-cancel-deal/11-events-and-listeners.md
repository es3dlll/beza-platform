# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: DealCancelled

```php
<?php
// app/Events/DealCancelled.php

namespace App\Events;

use App\Models\Deal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DealCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Deal      $deal,
        public readonly Collection $investments,
        public readonly string    $reason,
    ) {}
}
```

## Listener: SendRefundNotification

```php
<?php
// app/Listeners/SendRefundNotification.php

namespace App\Listeners;

use App\Events\DealCancelled;
use App\Notifications\RefundProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendRefundNotification implements ShouldQueue
{
    public function handle(DealCancelled $event): void
    {
        foreach ($event->investments as $investment) {
            try {
                $investment->investor->notify(new RefundProcessed(
                    dealTitle: $event->deal->title,
                    amount:    $investment->amount,
                    currency:  $event->deal->currency,
                    reason:    $event->reason,
                ));
            } catch (\Throwable $e) {
                Log::warning('فشل إشعار الاسترجاع', [
                    'investor_id' => $investment->investor_id,
                ]);
            }
        }
    }
}
```
