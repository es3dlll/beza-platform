# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: InvestmentMade

```php
<?php
// app/Events/InvestmentMade.php

namespace App\Events;

use App\Models\Deal;
use App\Models\DealInvestment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvestmentMade
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DealInvestment $investment,
        public readonly User          $investor,
        public readonly Deal          $deal,
    ) {}
}
```

## Listener: SendInvestmentNotification

```php
<?php
// app/Listeners/SendInvestmentNotification.php

namespace App\Listeners;

use App\Events\InvestmentMade;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendInvestmentNotification implements ShouldQueue
{
    public function handle(InvestmentMade $event): void
    {
        try {
            $event->investor->notify(new \App\Notifications\InvestmentConfirmed(
                investment: $event->investment,
                deal:       $event->deal,
            ));
        } catch (\Throwable $e) {
            Log::error('فشل إشعار الاستثمار', [
                'investment_id' => $event->investment->id,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
```
