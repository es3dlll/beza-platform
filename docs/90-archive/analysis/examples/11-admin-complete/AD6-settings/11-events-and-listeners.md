# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: SettingsUpdated

```php
<?php
// app/Events/Admin/SettingsUpdated.php

namespace App\Events\Admin;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SettingsUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array $changes,
    ) {}
}
```

## Listener: LogSettingsChange

```php
<?php
// app/Listeners/Admin/LogSettingsChange.php

namespace App\Listeners\Admin;

use App\Events\Admin\SettingsUpdated;
use Illuminate\Support\Facades\Log;

class LogSettingsChange
{
    public function handle(SettingsUpdated $event): void
    {
        Log::info('Settings updated', [
            'changes'  => $event->changes,
            'admin_id' => auth()->id(),
            'ip'       => request()->ip(),
        ]);
    }
}
```

## Listener: ApplyMaintenanceMode

```php
<?php
// app/Listeners/Admin/ApplyMaintenanceMode.php

namespace App\Listeners\Admin;

use App\Events\Admin\SettingsUpdated;
use Illuminate\Support\Facades\Artisan;

class ApplyMaintenanceMode
{
    public function handle(SettingsUpdated $event): void
    {
        $changes = $event->changes;

        if (isset($changes['maintenance_mode'])) {
            if ($changes['maintenance_mode']) {
                Artisan::call('down', [
                    '--secret' => config('beza.maintenance_secret'),
                ]);
            } else {
                Artisan::call('up');
            }
        }
    }
}
```

## EventServiceProvider

```php
protected $listen = [
    SettingsUpdated::class => [
        LogSettingsChange::class,
        ApplyMaintenanceMode::class,
    ],
];
```
