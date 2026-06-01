# 12 - نظام الإشعارات (Notification System)

## CardReportNotification

```php
<?php

namespace App\Notifications;

use App\Models\CardReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CardReportNotification extends Notification
{
    use Queueable;

    public function __construct(public CardReport $report) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Card Report Generated',
            'report_id' => $this->report->id,
            'period' => $this->report->period,
            'total_volume' => $this->report->total_volume,
        ];
    }
}
```

## القنوات (Channels)
- database: local storage
- mail: email with PDF attachment
