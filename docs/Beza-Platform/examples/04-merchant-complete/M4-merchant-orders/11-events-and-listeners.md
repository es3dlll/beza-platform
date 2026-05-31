# 11 - الأحداث والمستمعين

```php
<?php
namespace App\Events;
use App\Models\MerchantOrder;
use Illuminate\Foundation\Events\Dispatchable;

class OrderStatusUpdated { use Dispatchable; public function __construct(public readonly MerchantOrder $order) {} }
```

```php
<?php
namespace App\Listeners;
use App\Events\OrderStatusUpdated;
use App\Notifications\OrderStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusNotification implements ShouldQueue {
    public function handle(OrderStatusUpdated $event): void {
        $event->order->customer->notify(new OrderStatusChanged($event->order));
    }
}
```
