# 12 - نظام الإشعارات (Notification System)

## PaymentReceived Notification
```php
<?php
namespace AppNotifications;
use AppModelsPaymentLink;
use IlluminateNotificationsNotification;

class PaymentReceived extends Notification {
    public function __construct(private readonly PaymentLink $link) {}
    public function via($notifiable): array { return ['database', 'fcm']; }
    public function toArray($notifiable): array {
        return [
            'type' => 'payment_received',
            'title' => 'تم استلام دفعة',
            'body' => "استلام {$this->link->amount} {$this->link->currency}",
            'link_id' => $this->link->id,
            'token' => $this->link->token,
        ];
    }
}

class PaymentLinkCreatedNotification extends Notification {
    public function __construct(private readonly PaymentLink $link) {}
    public function via($notifiable): array { return ['database']; }
    public function toArray($notifiable): array {
        return [
            'type' => 'payment_link_created',
            'title' => 'تم إنشاء رابط دفع',
            'body' => "رابط دفع بقيمة {$this->link->amount} {$this->link->currency}",
            'link_id' => $this->link->id,
        ];
    }
}
```

## شرح الإشعارات
- PaymentReceived: إشعار فوري عند إتمام الدفع بنجاح
- PaymentLinkCreated: تأكيد إنشاء رابط الدفع
- القنوات: database للتخزين و FCM للإشعارات الفورية
