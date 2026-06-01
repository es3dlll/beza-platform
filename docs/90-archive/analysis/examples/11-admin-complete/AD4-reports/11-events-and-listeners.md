# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: DailyReportGenerated

```php
<?php
// app/Events/Admin/DailyReportGenerated.php

namespace App\Events\Admin;

use App\DTOs\Admin\DailyReportData;
use Illuminate\Foundation\Events\Dispatchable;

class DailyReportGenerated
{
    use Dispatchable;

    public function __construct(
        public readonly DailyReportData $report,
    ) {}
}
```

## Listener: SendDailyReportDigest

```php
<?php
// app/Listeners/Admin/SendDailyReportDigest.php

namespace App\Listeners\Admin;

use App\Events\Admin\DailyReportGenerated;
use App\Notifications\Admin\DailyReportDigest;
use Illuminate\Support\Facades\Notification;

class SendDailyReportDigest
{
    public function handle(DailyReportGenerated $event): void
    {
        $admins = User::where('is_admin', true)->get();

        Notification::send($admins, new DailyReportDigest($event->report));
    }
}
```

## EventServiceProvider

```php
protected $listen = [
    DailyReportGenerated::class => [
        SendDailyReportDigest::class,
    ],
];
```
