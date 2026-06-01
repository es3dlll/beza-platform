# 12 - نظام الإشعارات (Notification System) للتسوية

## نظرة عامة
نظام الإشعارات يرسل تنبيهات فورية للتجار حول حالة طلبات التسوية البنكية عبر ثلاث قنوات: FCM (تطبيق الجوال)، البريد الإلكتروني، والرسائل النصية SMS. يمكن للتاجر تخصيص إعدادات الإشعارات حسب رغبته.

```php
<?php

namespace App\Notifications;

use App\Models\MerchantSettlement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Channels\FcmChannel;
use App\Channels\SmsChannel;

// ===== إشعار إتمام التسوية بنجاح =====

class SettlementCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly MerchantSettlement $settlement
    ) {
        $this->onQueue('notifications');
    }

    /**
     * تحديد قنوات الإرسال حسب تفضيلات التاجر
     */
    public function via($notifiable): array
    {
        $channels = ['database']; // الحفظ في قاعدة البيانات دائمًا

        if ($notifiable->notification_preferences['settlement_completed']['email'] ?? true) {
            $channels[] = 'mail';
        }
        if ($notifiable->notification_preferences['settlement_completed']['fcm'] ?? true) {
            $channels[] = FcmChannel::class;
        }
        if ($notifiable->notification_preferences['settlement_completed']['sms'] ?? false) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    /**
     * تمثيل الإشعار في قاعدة البيانات
     */
    public function toArray($notifiable): array
    {
        return [
            'type'          => 'settlement_completed',
            'title'         => '✅ تمت التسوية البنكية بنجاح',
            'body'          => "تم تحويل {$this->settlement->net_amount} {$this->settlement->currency} إلى حسابك البنكي ({$this->settlement->bank_name}). تاريخ التسوية: {$this->settlement->settlement_date->format('Y-m-d H:i')}",
            'settlement_id' => $this->settlement->id,
            'net_amount'    => $this->settlement->net_amount,
            'currency'      => $this->settlement->currency,
            'bank_name'     => $this->settlement->bank_name,
            'timestamp'     => now()->toIso8601String(),
        ];
    }

    /**
     * تمثيل الإشعار في البريد الإلكتروني
     */
    public function toMail($notifiable): MailMessage
    {
        $merchantName = $notifiable->name;

        return (new MailMessage)
            ->subject("✅ تمت التسوية البنكية - {$this->settlement->net_amount} {$this->settlement->currency}")
            ->greeting("مرحباً {$merchantName}،")
            ->line("تم تحويل مبلغ التسوية إلى حسابك البنكي بنجاح.")
            ->line("المبلغ الصافي: **{$this->settlement->net_amount} {$this->settlement->currency}**")
            ->line("البنك: {$this->settlement->bank_name}")
            ->line("رقم الحساب: {$this->settlement->bank_account_number}")
            ->line("مرجع التحويل: {$this->settlement->bank_transaction_ref}")
            ->line("تاريخ التسوية: {$this->settlement->settlement_date->format('Y-m-d H:i:s')}")
            ->action('عرض التفاصيل', url("/merchant/settlements/{$this->settlement->id}"))
            ->line('شكراً لاستخدامك Beza!');
    }

    /**
     * تمثيل الإشعار في FCM (تطبيق الجوال)
     */
    public function toFcm($notifiable): array
    {
        return [
            'title' => '✅ تمت التسوية البنكية',
            'body'  => "تم تحويل {$this->settlement->net_amount} {$this->settlement->currency} إلى {$this->settlement->bank_name}",
            'data'  => [
                'type'          => 'settlement_completed',
                'settlement_id' => (string) $this->settlement->id,
                'screen'        => 'SettlementDetails',
            ],
        ];
    }

    /**
     * تمثيل الإشعار في SMS
     */
    public function toSms($notifiable): string
    {
        return "Beza: تم تحويل {$this->settlement->net_amount} {$this->settlement->currency} إلى حسابك البنكي. المرجع: {$this->settlement->bank_transaction_ref}";
    }
}
```

## إشعار فشل التسوية

```php
class SettlementFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly MerchantSettlement $settlement,
        private readonly string $reason
    ) {
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        return ['database', 'mail', FcmChannel::class];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'          => 'settlement_failed',
            'title'         => '❌ فشلت التسوية البنكية',
            'body'          => "لم تتم التسوية البنكية بقيمة {$this->settlement->net_amount} {$this->settlement->currency}. السبب: {$this->reason}. تم إعادة الرصيد إلى محفظتك.",
            'settlement_id' => $this->settlement->id,
            'reason'        => $this->reason,
            'action_required' => true,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("❌ فشلت التسوية البنكية - {$this->settlement->id}")
            ->greeting("عزيزي {$notifiable->name}،")
            ->line("نأسف، لم تتم التسوية البنكية بسبب: {$this->reason}")
            ->line("تم إعادة الرصيد إلى محفظتك في Beza.")
            ->line("المبلغ: {$this->settlement->net_amount} {$this->settlement->currency}")
            ->line("يرجى التحقق من صحة بيانات حسابك البنكي وإعادة المحاولة.")
            ->action('تصحيح البيانات البنكية', url("/merchant/bank-accounts"))
            ->line('نعتذر عن الإزعاج.');
    }
}
```

## إشعار تقرير التسوية الدوري

```php
class SettlementSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Merchant $merchant,
        private readonly array $periodSettlements,
        private readonly string $period
    ) {}

    /**
     * إرسال تقرير شامل عن التسوية في نهاية كل دورة
     * يحتوي على: إجمالي المبيعات، العمولات، صافي المبلغ
     */
    public function toMail($notifiable): MailMessage
    {
        $totalGross = collect($this->periodSettlements)->sum('gross_amount');
        $totalNet = collect($this->periodSettlements)->sum('net_amount');
        $totalFees = $totalGross - $totalNet;

        return (new MailMessage)
            ->subject("📊 تقرير التسوية {$this->period}")
            ->greeting("مرحباً {$notifiable->name}،")
            ->line("إليك ملخص التسوية للفترة {$this->period}:")
            ->line("إجمالي المبيعات: **{$totalGross} USD**")
            ->line("إجمالي الخصومات (عمولات + رسوم): **{$totalFees} USD**")
            ->line("صافي المبلغ المحول: **{$totalNet} USD**")
            ->line("عدد طلبات التسوية: " . count($this->periodSettlements))
            ->action('عرض التقارير الكاملة', url("/merchant/reports/settlements"))
            ->line('شكراً لثقتك بمنصة Beza');
    }
}
```

## تفضيلات الإشعارات (Notification Preferences)

```php
<?php

namespace App\Models\Traits;

trait HasNotificationPreferences
{
    /**
     * تفضيلات الإشعارات الافتراضية للتاجر
     * يتحكم التاجر بها من صفحة الإعدادات في التطبيق
     */
    public function getDefaultNotificationPreferences(): array
    {
        return [
            'settlement_completed' => [
                'email' => true,
                'fcm'   => true,
                'sms'   => false,
            ],
            'settlement_failed' => [
                'email' => true,
                'fcm'   => true,
                'sms'   => true,
            ],
            'settlement_summary' => [
                'email' => true,
                'fcm'   => false,
                'sms'   => false,
            ],
            'bank_account_expiring' => [
                'email' => true,
                'fcm'   => true,
                'sms'   => true,
            ],
        ];
    }
}
```

## ملخص أنواع الإشعارات

| نوع الإشعار | الحالة | FCM | Email | SMS | أولوية |
|------------|--------|-----|-------|-----|--------|
| إتمام التسوية بنجاح | completed | ✅ | ✅ | اختياري | عالية |
| فشل التسوية | failed | ✅ | ✅ | ✅ | عاجلة |
| طلب تسوية قيد المعالجة | processing | ✅ | ✅ | ❌ | متوسطة |
| تقرير التسوية الدوري | summary | ❌ | ✅ | ❌ | منخفضة |
| الحساب البنكي على وشك الانتهاء | warning | ✅ | ✅ | ✅ | عالية |
