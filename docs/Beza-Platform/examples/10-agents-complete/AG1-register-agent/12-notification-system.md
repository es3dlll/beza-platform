# 12 - نظام الإشعارات (Notification System) — تسجيل وكيل

## AgentRegistrationNotification

```php
<?php

namespace App\Notifications;

use App\Models\AgentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AgentRegistrationNotification extends Notification
{
    use Queueable;

    public function __construct(public AgentRequest $request) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm', 'mail'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Agent Registration',
            'status' => $this->request->status,
            'submitted_at' => $this->request->created_at,
        ];
    }
}
```

## القنوات (Channels)
- database: local storage
- fcm: push notification when status changes
- mail: email confirmation of submission/approval
