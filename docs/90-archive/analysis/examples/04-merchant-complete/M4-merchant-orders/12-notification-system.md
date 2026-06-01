# 12 - نظام الإشعارات (Notification System)

## نظرة عامة
نظام إشعارات متعدد القنوات يرسل إشعارات آنية وفورية عند كل حدث في دورة حياة الطلب. يشمل FCM لتطبيق الجوال، SMS للرسائل النصية، Email للفواتير والإيصالات.

## إشعار طلب جديد للتاجر (FCM)
```php
<?php
namespace App\Notifications;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewOrderNotification extends Notification
{
    public function __construct(private readonly Order $order) {}

    public function via($notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->fcm_token) $channels[] = 'fcm';
        if ($notifiable->email) $channels[] = 'mail';
        return $channels;
    }

    public function toFcm($notifiable): array
    {
        return [
            'title' => 'طلب جديد',
            'body'  => "لديك طلب جديد رقم #{$this->order->order_number} بقيمة {$this->order->grand_total} ل.س",
            'data'  => [
                'type'     => 'new_order',
                'order_id' => (string) $this->order->id,
                'sound'    => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'    => 'new_order',
            'title'   => 'طلب جديد',
            'body'    => "طلب #{$this->order->order_number} بقيمة {$this->order->grand_total} ل.س",
            'order_id' => $this->order->id,
        ];
    }
}
```

## إشعار SMS للعميل عند الشحن
```php
class OrderShippedSms extends Notification
{
    public function __construct(private readonly Order $order) {}

    public function via($notifiable): array
    {
        return $notifiable->phone ? ['sms'] : [];
    }

    public function toSms($notifiable): string
    {
        $tracking = $this->order->tracking_number ?? 'غير متوفر';
        return "تم شحن طلبك #{$this->order->order_number}. رقم التتبع: {$tracking}. شكراً لتسوقك من Beza!";
    }
}
```

## إشعار Email مع ملخص الطلب
```php
class OrderInvoiceEmail extends Notification
{
    public function __construct(private readonly Order $order) {}

    public function via($notifiable): array
    {
        return $notifiable->email ? ['mail'] : [];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("فاتورة الطلب #{$this->order->order_number}")
            ->view('emails.order-invoice', [
                'order' => $this->order,
                'items' => $this->order->items,
                'customer' => $this->order->customer,
            ]);
    }
}
```

## إشعار عام لتغيير الحالة
```php
class OrderStatusChangedNotification extends Notification
{
    public function __construct(private readonly Order $order) {}

    public function via($notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->fcm_token) $channels[] = 'fcm';
        if ($notifiable->email && $notifiable->notify_email) $channels[] = 'mail';
        return $channels;
    }

    public function toFcm($notifiable): array
    {
        $statusLabel = $this->order->status->label();
        return [
            'title' => "تحديث حالة الطلب #{$this->order->order_number}",
            'body'  => "أصبح الطلب: {$statusLabel}",
            'data'  => [
                'type'     => 'order_status',
                'order_id' => (string) $this->order->id,
                'status'   => $this->order->status->value,
            ],
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusLabel = $this->order->status->label();
        return (new MailMessage)
            ->subject("تحديث حالة الطلب #{$this->order->order_number}")
            ->line("تم تحديث حالة طلبك إلى: {$statusLabel}")
            ->action('تتبع الطلب', url("/orders/{$this->order->id}"));
    }
}
```

## إرسال الإشعارات من الخدمة
```php
class OrderNotificationService
{
    public function notifyNewOrder(Order $order): void
    {
        $merchant = $order->merchant;
        $merchant->notify(new NewOrderNotification($order));
    }

    public function notifyStatusChange(Order $order): void
    {
        // إشعار العميل
        $order->customer->notify(new OrderStatusChangedNotification($order));

        // إشعار التاجر إذا كان هناك ملاحظة
        if ($order->notes) {
            $order->merchant->notify(new OrderStatusChangedNotification($order));
        }

        // إرسال SMS إذا وصلت الحالة إلى shipped
        if ($order->status === OrderStatus::SHIPPED) {
            $order->customer->notify(new OrderShippedSms($order));
        }
    }

    public function notifyOrderCancelled(Order $order, string $reason): void
    {
        $order->customer->notify(new OrderStatusChangedNotification($order));
        // إشعار إضافي مع سبب الإلغاء
        $order->customer->notify(new OrderCancelledNotification($order, $reason));
    }
}
```

## تفضيلات الإشعارات لكل تاجر
```php
// في موديل Merchant
public function notificationPreferences(): array
{
    return [
        'new_order_sms'  => $this->settings['notify_new_order_sms'] ?? true,
        'new_order_email' => $this->settings['notify_new_order_email'] ?? true,
        'new_order_fcm'  => $this->settings['notify_new_order_fcm'] ?? true,
        'status_change'  => $this->settings['notify_status_change'] ?? true,
    ];
}
```

## جدول أحداث الإشعارات
| الحدث | القناة | المستلم | التوقيت |
|-------|--------|---------|---------|
| طلب جديد | FCM + Email | التاجر | فوري |
| تأكيد الطلب | FCM | العميل | فوري |
| تم الشحن | SMS + Email | العميل | فوري |
| تم التوصيل | FCM | العميل | فوري |
| إلغاء الطلب | FCM + Email | العميل | فوري |
| إرجاع الطلب | FCM | التاجر | فوري |
| تذكير بالتقييم | Email | العميل | بعد 3 أيام |
