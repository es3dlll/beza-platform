# 12 - نظام الإشعارات (Notification System)

## MerchantApplicationStatus Notification

```php
<?php
// app/Notifications/MerchantApplicationStatus.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MerchantApplicationStatus extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string  $status,
        private readonly string  $businessName,
        private readonly string  $message,
        private readonly ?string $rejectionReason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'fcm'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->status === 'approved'
            ? '✅ تم قبول طلب التسجيل كتاجر'
            : '❌ تم رفض طلب التسجيل كتاجر';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("مرحباً {$notifiable->name}")
            ->line($this->message);

        if ($this->status === 'approved') {
            $mail->action('الدخول للوحة التحكم', url('/login'));
        }

        return $mail;
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->status === 'approved'
                ? '✅ تم قبول طلبك كتاجر'
                : '❌ تم رفض طلبك كتاجر',
            'body'  => $this->message,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'merchant_application',
            'status'          => $this->status,
            'business_name'   => $this->businessName,
            'message'         => $this->message,
            'rejection_reason'=> $this->rejectionReason,
        ];
    }
}
```

## AgentApplicationStatus Notification

```php
<?php
// app/Notifications/AgentApplicationStatus.php
// نفس هيكل MerchantApplicationStatus مع تعديل النصوص
```

## إشعار للمشرفين عند طلب جديد

```php
<?php
// app/Notifications/Admin/NewApplicationNotification.php

namespace App\Notifications\Admin;

class NewApplicationNotification extends Notification
{
    public function __construct(
        private readonly string $type, // 'merchant' or 'agent'
        private readonly string $applicantName,
        private readonly int    $applicationId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->type === 'merchant' ? 'تاجر' : 'وكيل';
        return [
            'type'            => 'new_application',
            'title'           => "طلب {$label} جديد",
            'body'            => "{$this->applicantName} قدم طلب {$label}",
            'application_id'  => $this->applicationId,
            'application_type'=> $this->type,
        ];
    }
}
```
