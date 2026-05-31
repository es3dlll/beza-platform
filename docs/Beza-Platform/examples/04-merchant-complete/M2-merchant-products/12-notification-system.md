# 12 - نظام الإشعارات (Notification System)

## ProductAdded Notification
```php
<?php
namespace AppNotifications;
use AppModelsMerchantProduct;
use IlluminateNotificationsNotification;

class ProductAdded extends Notification
{
    public function __construct(private readonly MerchantProduct $product) {}
    public function via($notifiable): array { return ['database', 'fcm']; }
    public function toArray($notifiable): array {
        return ['type' => 'product_added', 'title' => 'تم إضافة منتج', 'body' => "تم إضافة {$this->product->name}", 'product_id' => $this->product->id];
    }
}

class LowStockWarning extends Notification
{
    public function __construct(private readonly MerchantProduct $product) {}
    public function via($notifiable): array { return ['database', 'fcm', 'mail']; }
    public function toMail($notifiable): MailMessage {
        return (new MailMessage)->subject('مخزون منخفض')
            ->line("المخزون الحالي لـ {$this->product->name}: {$this->product->stock}")
            ->action('إدارة المنتجات', url('/merchant/products'));
    }
    public function toArray($notifiable): array {
        return ['type' => 'low_stock', 'title' => 'مخزون منخفض', 'body' => "مخزون {$this->product->name} منخفض: {$this->product->stock}", 'product_id' => $this->product->id];
    }
}
```

## شرح الإشعارات
- ProductAdded: إشعار فوري عند إضافة منتج جديد
- LowStockWarning: تنبيه عند انخفاض المخزون عن حد معين
- القنوات: database + fcm للإشعارات الفورية، mail للتنبيهات الهامة
