# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: DealCreated

```php
<?php
// app/Events/DealCreated.php

namespace App\Events;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DealCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Deal $deal,
        public readonly User $admin,
    ) {}
}
```

## Listener: SendDealCreatedNotification

```php
<?php
// app/Listeners/SendDealCreatedNotification.php

namespace App\Listeners;

use App\Events\DealCreated;
use App\Notifications\DealCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendDealCreatedNotification implements ShouldQueue
{
    public $maxAttempts = 3;

    public function handle(DealCreated $event): void
    {
        try {
            // إشعار للمشرفين الآخرين
            $admins = \App\Models\User::where('is_admin', true)->get();
            foreach ($admins as $admin) {
                $admin->notify(new DealCreatedNotification($event->deal, $event->admin));
            }
        } catch (\Throwable $e) {
            Log::error('فشل إشعار إنشاء الصفقة', [
                'deal_id' => $event->deal->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
```

## التسجيل في EventServiceProvider

```php
protected $listen = [
    DealCreated::class => [
        SendDealCreatedNotification::class,
    ],
];
```
