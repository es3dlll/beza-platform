# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: DashboardDataRefreshed

```php
<?php
// app/Events/Admin/DashboardDataRefreshed.php

namespace App\Events\Admin;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardDataRefreshed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array $oldStats,
        public readonly array $newStats,
    ) {}
}
```

## Listener: LogDashboardRefresh

```php
<?php
// app/Listeners/Admin/LogDashboardRefresh.php

namespace App\Listeners\Admin;

use App\Events\Admin\DashboardDataRefreshed;
use Illuminate\Support\Facades\Log;

class LogDashboardRefresh
{
    public function handle(DashboardDataRefreshed $event): void
    {
        $oldTotal = $event->oldStats['total_users'] ?? 0;
        $newTotal = $event->newStats['total_users'] ?? 0;

        Log::info('Dashboard data refreshed', [
            'old_users'    => $oldTotal,
            'new_users'    => $newTotal,
            'diff'         => $newTotal - $oldTotal,
            'cached_at'    => $event->newStats['cached_at'] ?? null,
        ]);
    }
}
```

## Listener: NotifyOnThreshold

```php
<?php
// app/Listeners/Admin/NotifyOnThreshold.php

namespace App\Listeners\Admin;

use App\Events\Admin\DashboardDataRefreshed;
use Illuminate\Support\Facades\Notification;

class NotifyOnThreshold
{
    private array $thresholds = [
        'total_users'   => ['drop' => 0.1],        // تنبيه إذا نقص المستخدمون 10%
        'total_fees'    => ['drop' => 0.2],        // تنبيه إذا نقصت الإيرادات 20%
        'merchants_count' => ['drop' => 0.05],     // تنبيه إذا نقص التجار 5%
    ];

    public function handle(DashboardDataRefreshed $event): void
    {
        foreach ($this->thresholds as $metric => $config) {
            $old = $event->oldStats[$metric] ?? 0;
            $new = $event->newStats[$metric] ?? 0;

            if ($old <= 0) continue;

            $change = ($new - $old) / $old;

            if ($change <= -($config['drop'])) {
                Notification::route('mail', config('beza.admin_email'))
                    ->notify(new ThresholdAlertNotification(
                        metric: $metric,
                        oldValue: $old,
                        newValue: $new,
                        changePercent: $change * 100,
                    ));
            }
        }
    }
}
```

## Event Service Provider

```php
<?php
// app/Providers/EventServiceProvider.php

protected $listen = [
    \App\Events\Admin\DashboardDataRefreshed::class => [
        \App\Listeners\Admin\LogDashboardRefresh::class,
        \App\Listeners\Admin\NotifyOnThreshold::class,
    ],
];
```
