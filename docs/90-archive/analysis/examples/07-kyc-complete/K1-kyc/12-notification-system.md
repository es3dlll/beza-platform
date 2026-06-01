# 12 - نظام الإشعارات (FCM + SMS + Email)

## KycStatusChanged Notification

```php
<?php
// app/Notifications/KycStatusChanged.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class KycStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string  $status,
        private readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        if ($this->status === 'verified') {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('تم التحقق من هويتك')
                ->greeting('مرحباً ' . $notifiable->name)
                ->line('تم التحقق من وثائق هويتك بنجاح')
                ->line('يمكنك الآن استخدام المنصة بدون حدود')
                ->action('عرض المحفظة', url('/wallet'));
        }

        if ($this->status === 'rejected') {
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('لم يتم التحقق من هويتك')
                ->greeting('مرحباً ' . $notifiable->name)
                ->line('للأسف، لم يتم التحقق من وثائق هويتك')
                ->line("السبب: {$this->reason}")
                ->line('يرجى إعادة رفع الوثائق بصورة صحيحة')
                ->action('إعادة المحاولة', url('/kyc'));
        }

        // pending
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('استلمنا وثائقك')
            ->line('استلمنا وثائق هويتك وهي قيد المراجعة');
    }

    public function toArray(object $notifiable): array
    {
        $messages = [
            'verified' => 'تم التحقق من هويتك بنجاح',
            'rejected' => "لم يتم التحقق: {$this->reason}",
            'pending'  => 'المستندات قيد المراجعة',
        ];

        return [
            'type'  => 'kyc_status',
            'title' => 'تحديث حالة KYC',
            'body'  => $messages[$this->status] ?? 'تحديث حالة KYC',
        ];
    }
}
```

## KycPendingReview Notification (للمشرف)

```php
<?php
// app/Notifications/KycPendingReview.php

class KycPendingReview extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $user) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'             => 'kyc_pending_review',
            'title'            => 'طلب KYC جديد',
            'body'             => "المستخدم {$this->user->name} ينتظر مراجعة وثائقه",
            'user_id'          => $this->user->id,
        ];
    }
}
```
