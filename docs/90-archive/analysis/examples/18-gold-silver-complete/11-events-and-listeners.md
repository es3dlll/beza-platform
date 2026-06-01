# 11 - الأحداث والمستمعين (Events & Listeners)

## هيكل الأحداث

```
GoldPurchased ──► SendPurchaseReceipt          (FCM + SMS)
               ──► UpdateHoldingValuation       (Update cached values)

GoldSold ──► SendSaleReceipt                    (FCM + SMS)

PriceAlertTriggered ──► SendPriceAlertNotification (FCM)
```

## GoldPurchased Event

```php
<?php
// app/Events/GoldPurchased.php

namespace App\Events;

use App\Models\CommodityTransaction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoldPurchased
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CommodityTransaction $transaction,
        public User                 $user,
    ) {}
}
```

## GoldSold Event

```php
<?php
// app/Events/GoldSold.php

namespace App\Events;

use App\Models\CommodityTransaction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoldSold
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CommodityTransaction $transaction,
        public User                 $user,
    ) {}
}
```

## PriceAlertTriggered Event

```php
<?php
// app/Events/PriceAlertTriggered.php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PriceAlertTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User   $user,
        public string $commodity,
        public float  $currentPrice,
        public float  $targetPrice,
        public string $direction, // 'above' | 'below'
    ) {}
}
```

## SendPurchaseReceipt Listener

```php
<?php
// app/Listeners/SendPurchaseReceipt.php

namespace App\Listeners;

use App\Events\GoldPurchased;
use App\Notifications\PurchaseConfirmedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPurchaseReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function handle(GoldPurchased $event): void
    {
        $transaction = $event->transaction;
        $user = $event->user;

        $commodityName = $transaction->commodity === 'gold' ? 'الذهب' : 'الفضة';

        $user->notify(new PurchaseConfirmedNotification(
            commodityName:  $commodityName,
            grams:          (float) $transaction->grams,
            totalUsd:       (float) $transaction->total_usd,
            fee:            (float) $transaction->fee,
            referenceNumber: $transaction->reference_number,
        ));
    }
}
```

## SendSaleReceipt Listener

```php
<?php
// app/Listeners/SendSaleReceipt.php

namespace App\Listeners;

use App\Events\GoldSold;
use App\Notifications\SaleConfirmedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSaleReceipt implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function handle(GoldSold $event): void
    {
        $transaction = $event->transaction;
        $user = $event->user;

        $commodityName = $transaction->commodity === 'gold' ? 'الذهب' : 'الفضة';

        $user->notify(new SaleConfirmedNotification(
            commodityName: $commodityName,
            grams:         (float) $transaction->grams,
            totalUsd:      (float) $transaction->total_usd,
            fee:           (float) $transaction->fee,
            referenceNumber: $transaction->reference_number,
        ));
    }
}
```

## UpdateHoldingValuation Listener

```php
<?php
// app/Listeners/UpdateHoldingValuation.php

namespace App\Listeners;

use App\Events\GoldPurchased;
use App\Models\CommodityHolding;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class UpdateHoldingValuation implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'low';

    /**
     * تحديث التقييم المخبأ للحيازة
     * هذا يضمن أن عرض المحفظة يعكس آخر سعر
     */
    public function handle(GoldPurchased $event): void
    {
        $userId = $event->user->id;

        // مسح الكاش الخاص بحيازات هذا المستخدم
        Cache::forget("holding_valuation_{$userId}");

        // إعادة تقييم جميع حيازات المستخدم
        $holdings = CommodityHolding::where('user_id', $userId)->get();

        foreach ($holdings as $holding) {
            Cache::put(
                "holding_value_{$holding->id}",
                $holding->current_value_usd,
                300 // 5 دقائق
            );
        }
    }
}
```

## SendPriceAlertNotification Listener

```php
<?php
// app/Listeners/SendPriceAlertNotification.php

namespace App\Listeners;

use App\Events\PriceAlertTriggered;
use App\Notifications\PriceAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPriceAlertNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';

    public function handle(PriceAlertTriggered $event): void
    {
        $commodityName = $event->commodity === 'gold' ? 'الذهب' : 'الفضة';
        $directionText = $event->direction === 'above' ? 'تجاوز' : 'انخفض إلى';

        $event->user->notify(new PriceAlertNotification(
            commodityName: $commodityName,
            currentPrice:  $event->currentPrice,
            targetPrice:   $event->targetPrice,
            direction:     $directionText,
        ));
    }
}
```

## تسجيل الأحداث والمستمعين

```php
<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Events\GoldPurchased;
use App\Events\GoldSold;
use App\Events\PriceAlertTriggered;
use App\Listeners\SendPurchaseReceipt;
use App\Listeners\SendSaleReceipt;
use App\Listeners\SendPriceAlertNotification;
use App\Listeners\UpdateHoldingValuation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        GoldPurchased::class => [
            SendPurchaseReceipt::class,
            UpdateHoldingValuation::class,
        ],
        GoldSold::class => [
            SendSaleReceipt::class,
        ],
        PriceAlertTriggered::class => [
            SendPriceAlertNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
```

## ملخص الأحداث

| الحدث | يُطلق من | المستمعون | التوقيت |
|-------|---------|-----------|---------|
| GoldPurchased | CommodityService::executeBuy | SendPurchaseReceipt + UpdateHoldingValuation | غير متزامن (Queue) |
| GoldSold | CommodityService::executeSell | SendSaleReceipt | غير متزامن (Queue) |
| PriceAlertTriggered | PriceFeedProvider (Scheduler) | SendPriceAlertNotification | غير متزامن (Queue) |
