# 12 - نظام الإشعارات (Notification System) — التسوية

## SettlementNotification

```php
<?php

namespace App\Notifications;

use App\Models\Settlement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SettlementNotification extends Notification
{
    use Queueable;

    public function __construct(public Settlement $settlement) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm', 'mail'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Settlement ' . $this->settlement->status,
            'amount' => $this->settlement->amount,
            'status' => $this->settlement->status,
            'settlement_id' => $this->settlement->id,
        ];
    }
}
```

## القنوات (Channels)
- database: local storage
- fcm: push notification
- mail: email with settlement summary
