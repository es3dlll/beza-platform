# 12 - الإشعارات

```php
<?php
namespace App\Notifications;
use App\Models\MerchantSubscription;
use Illuminate\Notifications\Notification;

class UpcomingCharge extends Notification {
    public function __construct(private readonly MerchantSubscription $sub) {}
    public function via($notifiable): array { return ['database', 'fcm']; }
    public function toArray($notifiable): array {
        return ['type' => 'upcoming_charge', 'title' => 'قرب موعد الاشتراك',
                'body' => "سيتم خصم {$this->sub->amount} {$this->sub->currency} بعد 3 أيام"];
    }
}

class ChargeCompleted extends Notification {
    public function __construct(private readonly MerchantSubscription $sub) {}
    public function toArray($notifiable): array {
        return ['type' => 'charge_completed', 'title' => 'تم تجديد الاشتراك',
                'body' => "تم خصم {$this->sub->amount} {$this->sub->currency} للدورة {$this->sub->current_cycle}"];
    }
}
```
