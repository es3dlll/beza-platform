# 12 - نظام الإشعارات (Notification System) — الخريطة

## NearbyAgentNotification

```php
<?php

namespace App\Notifications;

use App\Models\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NearbyAgentNotification extends Notification
{
    use Queueable;

    public function __construct(public Agent $agent, public float $distance) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Nearby Agent',
            'agent_name' => $this->agent->name,
            'distance' => $this->distance,
            'agent_id' => $this->agent->id,
        ];
    }
}
```

## القنوات (Channels)
- database: local storage
- fcm: push notification when agents are nearby
