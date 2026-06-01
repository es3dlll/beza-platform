# 12 - نظام الإشعارات (Notification System) - إصدار البطاقة

## CardIssuedNotification

```php
<?php

namespace App\Notifications;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CardIssuedNotification extends Notification
{
    use Queueable;

    public function __construct(public Card $card) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'New Card Issued',
            'card_id' => $this->card->id,
            'last_four' => $this->card->last_four,
            'type' => $this->card->type,
        ];
    }
}
```

## القنوات (Channels)
- database: stores notification in local DB
- fcm: push notification to mobile device
- mail: email confirmation
