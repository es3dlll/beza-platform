# 12 - نظام الإشعارات للوحة التحكم

## ThresholdAlertNotification

```php
<?php
// app/Notifications/Admin/ThresholdAlertNotification.php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ThresholdAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $metric,
        private readonly float  $oldValue,
        private readonly float  $newValue,
        private readonly float  $changePercent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ تنبيه: تغير كبير في ' . $this->metric)
            ->greeting('مرحباً أيها المشرف')
            ->line("تم رصد تغير كبير في مؤشر: {$this->metric}")
            ->line("القيمة السابقة: {$this->oldValue}")
            ->line("القيمة الحالية: {$this->newValue}")
            ->line("نسبة التغير: {$this->changePercent}%")
            ->action('عرض لوحة التحكم', url('/admin/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'threshold_alert',
            'metric'         => $this->metric,
            'old_value'      => $this->oldValue,
            'new_value'      => $this->newValue,
            'change_percent' => $this->changePercent,
            'title'          => "تنبيه: تغير في {$this->metric}",
            'body'           => "{$this->metric}: من {$this->oldValue} إلى {$this->newValue} ({$this->changePercent}%)",
        ];
    }
}
```

## إشعارات الأداء (Performance Alerts)

```php
<?php
// app/Notifications/Admin/SlowDashboardNotification.php

namespace App\Notifications\Admin;

use Illuminate\Notifications\Notification;

class SlowDashboardNotification extends Notification
{
    public function __construct(
        private readonly float $loadTimeMs,
        private readonly string $query,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'performance_alert',
            'title'     => '⚠️ أداء بطيء',
            'body'      => "استعلام dashboard استغرق {$this->loadTimeMs}ms",
            'query'     => $this->query,
            'load_time' => $this->loadTimeMs,
        ];
    }
}
```

## إحصائيات تُرسل دورياً

```
┌─────────────────────────────────────────────┐
│         Cron: Email Daily Digest             │
├─────────────────────────────────────────────┤
│  - إجمالي المستخدمين: 15,420 (+120 جديد)     │
│  - المعاملات: 28,450 (+5.2% عن أمس)          │
│  - الإيرادات: 452,000 SYP                    │
│  - التجار النشطون: 342                       │
│  - أعلى تاجر: متجر الإلكترونيات (850,000)    │
└─────────────────────────────────────────────┘
```

```php
// app/Console/Commands/SendDailyDigest.php
Artisan::command('admin:daily-digest', function () {
    $stats = app(DashboardStatsService::class)->getStats('1d');
    Notification::route('mail', config('beza.admin_email'))
        ->notify(new DailyDigestNotification($stats));
})->dailyAt('08:00');
```
