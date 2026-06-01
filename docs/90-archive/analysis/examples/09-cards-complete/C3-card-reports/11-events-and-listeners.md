# 11 - الأحداث والمستمعون (Events & Listeners)

## Event: CardReportGenerated

```php
<?php

namespace App\Events;

use App\Models\CardReport;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardReportGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CardReport $report,
    ) {}
}
```

## Listener: SendCardReportNotification

```php
<?php

namespace App\Listeners;

use App\Events\CardReportGenerated;
use App\Notifications\CardReportNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendCardReportNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public function handle(CardReportGenerated $event): void
    {
        $user = $event->report->card->user;
        $user->notify(new CardReportNotification($event->report));
    }

    public function failed(CardReportGenerated $event, \Throwable $e): void
    {
        Log::critical('Card report notification failed', [
            'report_id' => $event->report->id,
            'error' => $e->getMessage(),
        ]);
    }
}
```

## التسجيل (Registration)

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    CardReportGenerated::class => [
        SendCardReportNotification::class,
    ],
];
```

## Why Async?
- Heavy report generation runs in background
- User doesn't wait for PDF generation/email
- Queue retries automatically on failure
