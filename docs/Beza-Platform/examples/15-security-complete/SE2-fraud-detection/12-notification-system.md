# 12 - إشعارات الاحتيال (Notification System)

## إشعار المشرف بمعاملة مشبوهة

```php
<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Services\FraudDetection\FraudResult;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FraudAlertAdminNotification extends Notification
{
    public function __construct(
        private Transaction $transaction,
        private FraudResult $fraudResult,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تنبيه احتيال - Beza Platform')
            ->line('تم اكتشاف معاملة مشبوهة تحتاج مراجعة.')
            ->line("رقم المعاملة: {$this->transaction->reference_number}")
            ->line("المبلغ: {$this->transaction->amount} {$this->transaction->fromWallet?->currency}")
            ->line("مستوى الخطورة: {$this->fraudResult->getRiskLevel()}")
            ->line("درجة المخاطرة: {$this->fraudResult->score}/100")
            ->action('مراجعة المعاملة', url('/admin/fraud'))
            ->line('القواعد المشغلة:');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'fraud_alert',
            'transaction_id' => $this->transaction->id,
            'reference_number' => $this->transaction->reference_number,
            'amount' => $this->transaction->amount,
            'risk_score' => $this->fraudResult->score,
            'risk_level' => $this->fraudResult->getRiskLevel(),
            'triggered_rules' => array_map(
                fn($r) => ['rule' => $r->rule, 'message' => $r->message],
                $this->fraudResult->triggeredRules
            ),
        ];
    }
}
```

## إشعار المستخدم بمعاملة معلقة

```php
class TransactionPendingNotification extends Notification
{
    public function __construct(private Transaction $transaction) {}

    public function via($notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'transaction_pending_review',
            'title' => 'المعاملة قيد المراجعة',
            'message' => "معاملة بقيمة {$this->transaction->amount} تحتاج مراجعة. سيتم إشعارك بالنتيجة.",
        ];
    }
}
```

## إشعار أمني (محاولات PIN)

```php
class SuspiciousActivityNotification extends Notification
{
    public function __construct(
        private string $activity,
        private string $ip,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'sms'];
    }

    public function toSms($notifiable): string
    {
        return "Beza: تم رصد نشاط مشبوه في حسابك ({$this->activity}). إذا لم تقم بذلك، اتصل بالدعم.";
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('نشاط مشبوه - Beza')
            ->line("تم رصد: {$this->activity}")
            ->line("IP: {$this->ip}")
            ->line('إذا لم تقم بهذا النشاط، يرجى تغيير كلمة المرور فوراً.');
    }
}
```
