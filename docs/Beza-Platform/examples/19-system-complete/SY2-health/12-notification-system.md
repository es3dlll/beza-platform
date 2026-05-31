# 12 - نظام الإشعارات (Notification System)

**الرمز التشغيلي:** SY2-health  
**النوع:** نظام إشعارات (Notification System)

---

## نظرة عامة (Overview)

عند اكتشاف خدمة معطلة أو متدهورة، يتم إرسال إشعارات تلقائية للفريق الفني. النظام يدعم قنوات إشعارات متعددة ويسمح بتخصيص الرسائل.

---

## كلاس الإشعار (Notification Class)

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * إشعار تعطل خدمة في نظام التحقق الصحي
 * يرسل تفاصيل العطل للفريق الفني
 */
class ServiceDownNotification extends Notification
{
    use Queueable;

    /**
     * اسم الخدمة المعطلة
     *
     * @var string
     */
    protected string $serviceName;

    /**
     * رسالة الخطأ
     *
     * @var string
     */
    protected string $errorMessage;

    /**
     * وقت اكتشاف العطل
     *
     * @var string
     */
    protected string $detectedAt;

    /**
     * إنشاء إشعار جديد
     *
     * @param string $serviceName
     * @param string $errorMessage
     * @param string $detectedAt
     */
    public function __construct(
        string $serviceName,
        string $errorMessage,
        string $detectedAt
    ) {
        // ترجمة: تخزين معلومات العطل
        $this->serviceName = $serviceName;
        $this->errorMessage = $errorMessage;
        $this->detectedAt = $detectedAt;
    }

    /**
     * تحديد قنوات الإشعار
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        // ترجمة: دعم قنوات متعددة: البريد الإلكتروني وقاعدة البيانات
        $channels = ['mail'];

        // ترجمة: إضافة قناة SMS إذا كان متاحاً
        if (config('health.sms_enabled', false)) {
            $channels[] = 'nexmo';
        }

        // ترجمة: إضافة قناة Slack إذا كان متاحاً
        if (config('health.slack_enabled', false)) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * تحضير رسالة البريد الإلكتروني
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        // ترجمة: بناء رسالة البريد الإلكتروني
        return (new MailMessage)
            ->subject("🚨 تنبيه: خدمة {$this->serviceName} معطلة - منصة بيزا")
            ->greeting("تنبيه عطل في الخدمة!")
            ->line("الخدمة: **{$this->serviceName}**")
            ->line("الحالة: ❌ معطلة")
            ->line("الخطأ: {$this->errorMessage}")
            ->line("وقت الاكتشاف: {$this->detectedAt}")
            ->action('لوحة التحكم', url('/admin/system/health'))
            ->line('يرجى اتخاذ الإجراءات اللازمة فوراً.')
            ->salutation('فريق تشغيل منصة بيزا');
    }

    /**
     * تحضير رسالة SMS
     *
     * @param mixed $notifiable
     * @return string
     */
    public function toNexmo($notifiable): string
    {
        // ترجمة: رسالة SMS مختصرة
        return "بيزا: خدمة {$this->serviceName} معطلة! {$this->errorMessage} - {$this->detectedAt}";
    }

    /**
     * تحضير رسالة Slack
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toSlack($notifiable): array
    {
        // ترجمة: رسالة Slack بتنسيق جميل
        return [
            'username' => 'Beza Health Bot',
            'icon_emoji' => ':red_circle:',
            'attachments' => [
                [
                    'color' => '#dc3545',
                    'title' => "🚨 خدمة معطلة: {$this->serviceName}",
                    'text'  => "**الخدمة:** {$this->serviceName}\n**الخطأ:** {$this->errorMessage}\n**الوقت:** {$this->detectedAt}",
                    'footer' => 'Beza Health Check System',
                    'ts'     => now()->timestamp,
                ],
            ],
        ];
    }

    /**
     * تحويل الإشعار إلى مصفوفة للتخزين
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        // ترجمة: لتخزين الإشعار في قاعدة البيانات
        return [
            'service_name'  => $this->serviceName,
            'error_message' => $this->errorMessage,
            'detected_at'   => $this->detectedAt,
            'type'          => 'service_down',
            'severity'      => 'critical',
        ];
    }
}
```

---

## إشعار تدهور الخدمة

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * إشعار تدهور خدمة (تحذير)
 */
class ServiceDegradedNotification extends Notification
{
    use Queueable;

    /**
     * @var string
     */
    protected string $serviceName;

    /**
     * @var string
     */
    protected string $warningMessage;

    /**
     * @var string
     */
    protected string $detectedAt;

    /**
     * @param string $serviceName
     * @param string $warningMessage
     * @param string $detectedAt
     */
    public function __construct(
        string $serviceName,
        string $warningMessage,
        string $detectedAt
    ) {
        // ترجمة: تخزين معلومات التدهور
        $this->serviceName = $serviceName;
        $this->warningMessage = $warningMessage;
        $this->detectedAt = $detectedAt;
    }

    /**
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        // ترجمة: رسالة بريد إلكتروني تحذيرية
        return (new MailMessage)
            ->subject("⚠️ تنبيه: خدمة {$this->serviceName} متدهورة - منصة بيزا")
            ->greeting("تنبيه تدهور أداء!")
            ->line("الخدمة: **{$this->serviceName}**")
            ->line("الحالة: ⚠️ أداء متدهور")
            ->line("التحذير: {$this->warningMessage}")
            ->line("وقت الاكتشاف: {$this->detectedAt}")
            ->action('عرض التفاصيل', url('/admin/system/health'))
            ->line('يرجى مراقبة الخدمة.');
    }
}
```

---

## نظام الإشعارات عبر البريد الإلكتروني (Email Notification)

```php
<?php

namespace App\Services\Health;

use App\Notifications\ServiceDegradedNotification;
use App\Notifications\ServiceDownNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * خدمة إرسال الإشعارات للفريق الفني
 */
class HealthNotificationService
{
    /**
     * قائمة المستلمين للتنبيهات
     *
     * @var array
     */
    protected array $recipients;

    /**
     * إنشاء الخدمة
     */
    public function __construct()
    {
        // ترجمة: قراءة قائمة المستلمين من الإعدادات
        $this->recipients = config('health.alert_recipients', []);

        // ترجمة: يمكن أن تكون عناوين بريد أو أرقام هواتف
        if (empty($this->recipients)) {
            Log::warning('لا يوجد مستلمون للإشعارات الصحية. ' .
                'يرجى ضبط HEALTH_ALERT_RECIPIENTS في ملف .env');
        }
    }

    /**
     * إرسال إشعار تعطل خدمة
     *
     * @param string $serviceName
     * @param string $errorMessage
     * @return bool
     */
    public function notifyServiceDown(string $serviceName, string $errorMessage): bool
    {
        // ترجمة: التحقق من وجود مستلمين
        if (empty($this->recipients)) {
            return false;
        }

        try {
            // ترجمة: إرسال الإشعار عبر البريد الإلكتروني
            Notification::route('mail', $this->recipients)
                ->notify(new ServiceDownNotification(
                    $serviceName,
                    $errorMessage,
                    now()->toIso8601String()
                ));

            Log::channel('health')->info(
                "تم إرسال إشعار تعطل الخدمة: {$serviceName}"
            );

            return true;

        } catch (\Exception $e) {
            Log::channel('health')->error(
                "فشل إرسال إشعار تعطل الخدمة: {$e->getMessage()}"
            );

            return false;
        }
    }

    /**
     * إرسال إشعار تدهور خدمة
     *
     * @param string $serviceName
     * @param string $warningMessage
     * @return bool
     */
    public function notifyServiceDegraded(string $serviceName, string $warningMessage): bool
    {
        if (empty($this->recipients)) {
            return false;
        }

        try {
            Notification::route('mail', $this->recipients)
                ->notify(new ServiceDegradedNotification(
                    $serviceName,
                    $warningMessage,
                    now()->toIso8601String()
                ));

            Log::channel('health')->info(
                "تم إرسال إشعار تدهور الخدمة: {$serviceName}"
            );

            return true;

        } catch (\Exception $e) {
            Log::channel('health')->error(
                "فشل إرسال إشعار تدهور الخدمة: {$e->getMessage()}"
            );

            return false;
        }
    }

    /**
     * إرسال إشعار جماعي عن حالة النظام
     *
     * @param string $overallStatus
     * @param array $services
     * @return bool
     */
    public function notifySystemStatus(string $overallStatus, array $services): bool
    {
        // ترجمة: إرسال تقرير دوري عن حالة النظام
        if (empty($this->recipients)) {
            return false;
        }

        try {
            $upCount = count(array_filter($services, fn($s) => $s['status'] === 'up'));
            $downCount = count(array_filter($services, fn($s) => $s['status'] === 'down'));
            $totalCount = count($services);

            Notification::route('mail', $this->recipients)
                ->notify(new \App\Notifications\SystemStatusNotification(
                    $overallStatus,
                    $upCount,
                    $downCount,
                    $totalCount,
                    $services
                ));

            return true;

        } catch (\Exception $e) {
            Log::error("فشل إرسال تقرير حالة النظام: {$e->getMessage()}");
            return false;
        }
    }
}
```

---

## إعدادات الإشعارات (Notification Configuration)

### ملف `config/health.php`

```php
<?php
// config/health.php - إعدادات الإشعارات

return [
    // ... الإعدادات السابقة ...

    /*
     * قائمة المستلمين للتنبيهات
     * يمكن أن تكون بريد إلكتروني أو أرقام هواتف
     */
    'alert_recipients' => env('HEALTH_ALERT_RECIPIENTS')
        ? explode(',', env('HEALTH_ALERT_RECIPIENTS'))
        : [],

    /*
     * تفعيل إشعارات Slack
     */
    'slack_enabled' => env('HEALTH_SLACK_ENABLED', false),
    'slack_webhook' => env('HEALTH_SLACK_WEBHOOK', ''),

    /*
     * تفعيل إشعارات SMS عبر Nexmo/Vonage
     */
    'sms_enabled' => env('HEALTH_SMS_ENABLED', false),
    'sms_from'    => env('HEALTH_SMS_FROM', 'BEZA'),

    /*
     * تفعيل الإشعارات فقط للخدمات المهمة
     */
    'critical_services_only' => env('HEALTH_CRITICAL_ONLY', false),
];
```

### ملف `.env`

```
# إعدادات الإشعارات الصحية
HEALTH_ALERT_RECIPIENTS=ops@beza.com,sre@beza.com
HEALTH_SLACK_ENABLED=true
HEALTH_SLACK_WEBHOOK=https://hooks.slack.com/services/xxx/yyy/zzz
HEALTH_SMS_ENABLED=false
HEALTH_CRITICAL_ONLY=true
```

---

## ملخص الإشعارات (Notifications Summary)

| الإشعار (Notification) | المستوى (Level) | القنوات (Channels) | متى يرسل (When) |
|----------------------|----------------|-------------------|----------------|
| `ServiceDownNotification` | خطير (Critical) | بريد، SMS، Slack | عند تعطل أي خدمة |
| `ServiceDegradedNotification` | تحذير (Warning) | بريد فقط | عند تدهور أداء خدمة |
| `SystemStatusNotification` | معلومات (Info) | بريد | تقرير دوري (اختياري) |

```
اكتشاف عطل ← إنشاء إشعار ← إرسال عبر البريد ← استلام الفريق الفني
                                        ← إرسال عبر Slack
                                        ← إرسال عبر SMS
```
