# 12 - نظام الإشعارات (Notification System)

## CardStatusChangedNotification

```php
<?php

namespace App\Notifications;

use App\Models\Card;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CardStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public Card $card, public string $oldStatus, public string $newStatus) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Card Status Changed',
            'card_id' => $this->card->id,
            'from' => $this->oldStatus,
            'to' => $this->newStatus,
        ];
    }
}
```

## القنوات (Channels)
- database: local storage
- fcm: push notification on status change
- mail: email alert for freeze/cancel
