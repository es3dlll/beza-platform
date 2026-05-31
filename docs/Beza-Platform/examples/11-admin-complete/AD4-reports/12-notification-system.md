# 12 - نظام الإشعارات للتقارير

## DailyReportDigest Notification

```php
<?php
// app/Notifications/Admin/DailyReportDigest.php

namespace App\Notifications\Admin;

use App\DTOs\Admin\DailyReportData;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyReportDigest extends Notification
{
    use Queueable;

    public function __construct(
        private readonly DailyReportData $report,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("📊 التقرير اليومي — {$this->report->date}")
            ->greeting('مرحباً أيها المشرف')
            ->line("التقرير اليومي لـ {$this->report->date}")
            ->line("المعاملات: {$this->report->totalTransactions}")
            ->line("الحجم: {$this->report->totalVolume} SYP")
            ->line("الإيرادات: {$this->report->totalFees} SYP")
            ->line("مستخدمون جدد: {$this->report->newUsers}")
            ->line("مستخدمون نشطون: {$this->report->activeUsers}")
            ->action('عرض التقرير الكامل', url('/admin/reports/daily'))
            ->line("نسبة النمو: " . ($this->report->growthPercent ? "{$this->report->growthPercent}%" : '—'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'              => 'daily_report',
            'title'             => '📊 التقرير اليومي',
            'body'              => "{$this->report->totalTransactions} معاملة | {$this->report->totalFees} SYP إيرادات",
            'date'              => $this->report->date,
            'total_transactions'=> $this->report->totalTransactions,
            'total_fees'        => $this->report->totalFees,
            'new_users'         => $this->report->newUsers,
        ];
    }
}
```
