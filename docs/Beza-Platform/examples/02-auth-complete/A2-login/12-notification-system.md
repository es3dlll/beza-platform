# 12 - نظام الإشعارات (Notification System)

## LoginAlert Notification

```php
<?php
// app/Notifications/LoginAlert.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LoginAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string  $ip,
        private readonly ?string $deviceId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'تسجيل دخول جديد',
            'body'  => "تم تسجيل الدخول من جهاز جديد. إذا لم تكن أنت، يرجى تغيير كلمة المرور فوراً.",
            'data'  => [
                'type'      => 'login_alert',
                'ip'        => $this->ip,
                'device_id' => $this->deviceId,
                'time'      => now()->toIso8601String(),
            ],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'login_alert',
            'title'     => 'تسجيل دخول جديد',
            'body'      => "تم تسجيل الدخول من IP: {$this->ip}",
            'ip'        => $this->ip,
            'device_id' => $this->deviceId,
        ];
    }
}
```

## جدول الإشعارات

```bash
php artisan notifications:table
php artisan migrate
```

```php
// database/migrations/xxxx_xx_xx_create_notifications_table.php

Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('type');
    $table->morphs('notifiable');
    $table->text('data');
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});
```

## ملاحظات

| العنصر | القيمة |
|--------|--------|
| إشعار الدخول | اختياري — يرسل فقط عند تغيير الجهاز |
| القنوات | FCM + Database (إخطار داخلي) |
| الهدف | تنبيه المستخدم عند وجود نشاط غير معتاد |
