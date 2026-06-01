# 12 - نظام الإشعارات (Notification System) — لوحة التحكم

## DashboardAlertNotification

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DashboardAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public string $type, public array $data) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->data['title'] ?? 'Dashboard Alert',
            'type' => $this->type,
            'message' => $this->data['message'] ?? '',
        ];
    }
}
```

## القنوات (Channels)
- database: local storage
- fcm: real-time dashboard alerts
