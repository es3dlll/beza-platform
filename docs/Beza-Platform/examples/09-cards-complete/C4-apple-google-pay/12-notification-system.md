# 12 - نظام الإشعارات (Notification System)

## WalletTokenProvisionedNotification

```php
<?php

namespace App\Notifications;

use App\Models\WalletToken;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WalletTokenProvisionedNotification extends Notification
{
    use Queueable;

    public function __construct(public WalletToken $token) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Card Added to Wallet',
            'device' => $this->token->device_type,
            'added_at' => $this->token->created_at,
        ];
    }
}
```

## القنوات (Channels)
- database: local storage
- fcm: push notification
