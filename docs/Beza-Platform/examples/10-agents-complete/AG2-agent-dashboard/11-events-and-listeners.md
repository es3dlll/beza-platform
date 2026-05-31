# 11 - الأحداث والمستمعين (Events & Listeners) — لوحة التحكم

## Event: DashboardDataRefreshed

```php
<?php

namespace App\Events;

use App\Models\Agent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardDataRefreshed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Agent $agent,
        public readonly array $stats,
    ) {}
}
```

## Listener: UpdateDashboardCache

```php
<?php

namespace App\Listeners;

use App\Events\DashboardDataRefreshed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateDashboardCache implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public function handle(DashboardDataRefreshed $event): void
    {
        Cache::put(
            "agent_dashboard_{$event->agent->id}",
            $event->stats,
            now()->addMinutes(5)
        );
    }

    public function failed(DashboardDataRefreshed $event, \Throwable $e): void
    {
        Log::critical('Dashboard cache update failed', [
            'agent_id' => $event->agent->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

## التسجيل (Registration)

```php
protected $listen = [
    DashboardDataRefreshed::class => [
        UpdateDashboardCache::class,
    ],
];
```

## Why Async?
- Dashboard loads from cache, not real-time DB
- Cache refresh runs in background
- Queue retries automatically
