# 12 - نظام الإشعارات (Notification System)

```php
<?php
namespace App\Notifications;
use App\Models\Merchant;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MerchantApprovedNotification extends Notification
{
    public function __construct(private readonly Merchant $merchant) {}
    public function via($notifiable): array {
        $channels = ['database'];
        if ($notifiable->fcm_token) $channels[] = 'fcm';
        if ($notifiable->email) $channels[] = 'mail';
        return $channels;
    }
    public function toMail($notifiable): MailMessage {
        return (new MailMessage)->subject('تم الموافقة على متجرك')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line("تم تفعيل {$this->merchant->business_name}")
            ->action('الدخول', url('/merchant/dashboard'));
    }
    public function toArray($notifiable): array {
        return ['type' => 'merchant_approved', 'title' => 'تم الموافقة',
                'body' => "تم تفعيل {$this->merchant->business_name}",
                'merchant_id' => $this->merchant->id];
    }
}
```
