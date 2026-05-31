# 11 - الأحداث والمستمعون (Events & Listeners)

## Event: InstallationCompleted

```php
<?php
// app/Events/InstallationCompleted.php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstallationCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array{name: string, email: string, phone: string} $adminData بيانات المشرف الأول
     */
    public function __construct(
        public readonly array $adminData,
    ) {}
}
```

## Listener: LogInstallationComplete

```php
<?php
// app/Listeners/Install/LogInstallationComplete.php

namespace App\Listeners\Install;

use App\Events\InstallationCompleted;
use Illuminate\Support\Facades\Log;

class LogInstallationComplete
{
    /**
     * تسجيل إكمال التنصيب في سجل الأحداث
     */
    public function handle(InstallationCompleted $event): void
    {
        Log::info('=== Beza Installation Completed ===');
        Log::info('تم إكمال تنصيب منصة Beza بنجاح', [
            'admin_name'       => $event->adminData['name'],
            'admin_email'      => $event->adminData['email'],
            'php_version'      => PHP_VERSION,
            'app_url'          => env('APP_URL'),
            'app_env'          => env('APP_ENV'),
            'db_connection'    => env('DB_CONNECTION'),
            'db_host'          => env('DB_HOST'),
            'db_name'          => env('DB_DATABASE'),
            'redis_host'       => env('REDIS_HOST'),
            'queue_connection' => env('QUEUE_CONNECTION'),
            'completed_at'     => now()->toIso8601String(),
        ]);
        Log::info('===================================');
    }
}
```

## Listener: SendAdminNotificationEmail (اختياري)

```php
<?php
// app/Listeners/Install/SendAdminNotificationEmail.php

namespace App\Listeners\Install;

use App\Events\InstallationCompleted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAdminNotificationEmail
{
    /**
     * إرسال إيميل للمشرف بتأكيد اكتمال التنصيب
     * ملاحظة: هذا يعتمد على إعدادات SMTP التي تم ضبطها في الخطوة 3
     * إذا فشل الإرسال، نكتفي بالتسجيل في السجل
     */
    public function handle(InstallationCompleted $event): void
    {
        if (empty(env('MAIL_HOST'))) {
            Log::info('لم يتم إرسال إيميل الترحيب — لم يتم ضبط SMTP');
            return;
        }

        try {
            $data = [
                'adminName' => $event->adminData['name'],
                'appName'   => env('APP_NAME', 'Beza'),
                'appUrl'    => env('APP_URL'),
                'email'     => $event->adminData['email'],
            ];

            // يمكن إرسال إيميل باستخدام Mailable
            // Mail::to($event->adminData['email'])->send(new InstallationCompleteMail($data));

            Log::info('تم إرسال إيميل الترحيب للمشرف', [
                'email' => $event->adminData['email'],
            ]);

        } catch (\Throwable $e) {
            Log::warning('فشل إرسال إيميل الترحيب للمشرف', [
                'email' => $event->adminData['email'],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

## تسجيل الـ Event & Listener

```php
<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Events\InstallationCompleted;
use App\Listeners\Install\LogInstallationComplete;
use App\Listeners\Install\SendAdminNotificationEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InstallationCompleted::class => [
            LogInstallationComplete::class,
            SendAdminNotificationEmail::class,
        ],
    ];
}
```

## متى يتم إطلاق الحدث؟

يتم إطلاق `InstallationCompleted` في الخطوة الأخيرة من التنصيب:

```php
// في InstallerController@complete
InstallationCompleted::dispatch($adminData);
```

بعد إطلاق الحدث:
1. يسجل `LogInstallationComplete` تفاصيل التنصيب في السجل
2. يحاول `SendAdminNotificationEmail` إرسال إيميل للمشرف (إذا كان SMTP مضبوطاً)
3. يتم تعطيل المثبت تلقائياً
4. تعود الواجهة بعرض ملخص التنصيب
