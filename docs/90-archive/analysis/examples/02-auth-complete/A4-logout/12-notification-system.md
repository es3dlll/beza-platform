# 12 - نظام الإشعارات (Notification System) — تسجيل الخروج

## نظرة عامة

على عكس العمليات الأخرى، تسجيل الخروج لا يرسل إشعاراً للمستخدم الذي نفذ العملية (لأن المستخدم هو من طلبها). لكن قد نحتاج إشعارات في حالات أمنية محددة.

## حالات إرسال الإشعارات

| الحالة | نوع الإشعار | القناة | الأولوية |
|--------|------------|--------|----------|
| تسجيل خروج من جهاز غير معروف | تنبيه أمني | FCM + Email | عالية |
| محاولة خروج فاشلة (token قديم) | إنذار أمني | Email | متوسطة |
| خروج جميع الأجهزة (اشتباه اختراق) | تأكيد عملية | FCM + Email | عالية |
| تغيير كلمة المرور + خروج إجباري | إشعار إجباري | FCM + Email | قصوى |

## 1. تنبيه بتسجيل الخروج من جهاز غير معروف

عند اكتشاف جهاز غير مألوف يقوم بتسجيل الخروج، نرسل إشعاراً فورياً:

### كود FCM Notification

```php
<?php
// app/Notifications/UnknownDeviceLogoutAlert.php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Kreait\Firebase\Messaging\CloudMessage;

class UnknownDeviceLogoutAlert extends Notification
{
    use Queueable;

    public function __construct(
        private readonly array $deviceInfo,
        private readonly string $ipAddress,
    ) {}

    public function via(User $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🔴 تنبيه أمني: تسجيل خروج من جهاز غير معروف')
            ->greeting("مرحباً {$notifiable->name}")
            ->line('تم تسجيل الخروج من حسابك باستخدام جهاز غير معروف سابقاً.')
            ->line("الجهاز: {$this->deviceInfo['device_name']}")
            ->line("نظام التشغيل: {$this->deviceInfo['os']}")
            ->line("المتصفح: {$this->deviceInfo['browser']}")
            ->line("IP: {$this->ipAddress}")
            ->line("الوقت: " . now()->toDateTimeString())
            ->line('إذا لم تكن أنت من نفذ هذه العملية، يرجى تغيير كلمة المرور فوراً.')
            ->action('تأمين الحساب', url('/security/change-password'));
    }

    public function toFcm(User $notifiable): CloudMessage
    {
        return CloudMessage::withTarget('token', $notifiable->fcm_token)
            ->withNotification([
                'title' => '🔴 تنبيه أمني',
                'body'  => 'تم تسجيل خروج من جهاز غير معروف. تحقق من نشاطك.',
            ])
            ->withData([
                'type' => 'security_alert',
                'screen' => 'login_history',
            ]);
    }

    public function toArray(User $notifiable): array
    {
        return [
            'type'       => 'unknown_device_logout',
            'device'     => $this->deviceInfo['device_name'],
            'ip'         => $this->ipAddress,
            'time'       => now()->toIso8601String(),
            'read'       => false,
        ];
    }
}
```

### معرفة الجهاز — Middleware

```php
// في app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        \App\Http\Middleware\TrackDeviceInfo::class,
        // ...
    ],
];
```

```php
<?php
// app/Http/Middleware/TrackDeviceInfo.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackDeviceInfo
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            $knownDevices = cache()->get(
                "known_devices:{$request->user()->id}", []
            );

            $currentDevice = sha1(
                $request->userAgent() . '|' . $request->ip()
            );

            $request->attributes->set('device_fingerprint', $currentDevice);
            $request->attributes->set('is_known_device',
                in_array($currentDevice, $knownDevices)
            );
        }

        return $next($request);
    }
}
```

## 2. إشعار بريد إلكتروني لتسجيل الخروج المشبوه

عند حدوث تسجيل خروج بظروف غير طبيعية (جهاز جديد، IP غير مألوف، وقت غير معتاد):

```php
<?php
// app/Notifications/SuspiciousLogoutAlert.php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SuspiciousLogoutAlert extends Notification
{
    public function __construct(
        private readonly array $context,
    ) {}

    public function via(User $notifiable): array
    {
        // البريد الإلكتروني فقط لهذا التنبيه الحساس
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $reasons = collect($this->context['suspicious_flags'])
            ->map(fn(string $flag) => "- {$flag}")
            ->implode("\n");

        return (new MailMessage)
            ->subject('⚠️ نشاط مشبوه في حسابك — Beza')
            ->greeting("تنبيه أمني مهم")
            ->line('تم رصد نشاط غير عادي عند تسجيل الخروج من حسابك:')
            ->line($reasons)
            ->line("الوقت: {$this->context['time']}")
            ->line("IP: {$this->context['ip']}")
            ->line("الموقع التقريبي: {$this->context['location']}")
            ->line('---')
            ->line('إذا كنت تعتقد أن هناك خطأ، يرجى التواصل مع الدعم الفني.')
            ->action('مراجعة نشاط الحساب', url('/security/activity'));
    }
}
```

### كشف الظروف المشبوهة

```php
// في LogoutService
private function detectSuspiciousContext(Request $request): array
{
    $flags = [];

    if (! $request->attributes->get('is_known_device')) {
        $flags[] = 'جهاز غير معروف';
    }

    $hour = now()->hour;
    if ($hour < 6 || $hour > 23) {
        $flags[] = 'وقت غير معتاد ('.$hour.':00)';
    }

    $lastLogout = LogoutLog::where('user_id', $request->user()->id)
        ->latest()
        ->first();

    if ($lastLogout && $lastLogout->created_at->diffInMinutes(now()) < 1) {
        $flags[] = 'تسجيل خروج متكرر في فترة قصيرة';
    }

    return [
        'suspicious_flags' => $flags,
        'time'             => now()->toDateTimeString(),
        'ip'               => $request->ip(),
        'location'         => geoip($request->ip())->city ?? 'غير معروف',
    ];
}
```

## 3. إعدادات الإشعارات — تفضيلات المستخدم

يجب أن يتحكم المستخدم بالإشعارات الأمنية:

```php
<?php
// app/Models/UserNotificationPreference.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'logout_alerts_email',     // boolean
        'logout_alerts_fcm',       // boolean
        'suspicious_login_alerts', // boolean
        'daily_security_summary',  // boolean
    ];

    protected $casts = [
        'logout_alerts_email'     => 'boolean',
        'logout_alerts_fcm'       => 'boolean',
        'suspicious_login_alerts' => 'boolean',
        'daily_security_summary'  => 'boolean',
    ];

    public static function defaults(): self
    {
        return new static([
            'logout_alerts_email'     => true,
            'logout_alerts_fcm'       => true,
            'suspicious_login_alerts' => true,
            'daily_security_summary'  => false,
        ]);
    }
}
```

### واجهة إدارة التفضيلات

```dart
// lib/features/settings/notification_preferences_screen.dart

class NotificationPreferencesScreen extends StatefulWidget {
  @override
  State<NotificationPreferencesScreen> createState() =>
      _NotificationPreferencesScreenState();
}

class _NotificationPreferencesScreenState
    extends State<NotificationPreferencesScreen> {
  bool _logoutAlertsEmail = true;
  bool _logoutAlertsFcm = true;
  bool _suspiciousAlerts = true;
  bool _dailySummary = false;
  bool _loading = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('إعدادات الإشعارات')),
      body: _loading
          ? Center(child: CircularProgressIndicator())
          : ListView(
              children: [
                SwitchListTile(
                  title: Text('تنبيهات تسجيل الخروج (بريد إلكتروني)'),
                  subtitle: Text('عند تسجيل خروج من جهاز جديد'),
                  value: _logoutAlertsEmail,
                  onChanged: (v) => _update('logout_alerts_email', v),
                ),
                SwitchListTile(
                  title: Text('تنبيهات تسجيل الخروج (إشعار جوال)'),
                  subtitle: Text('إرسال FCM عند الخروج من جهاز غير معروف'),
                  value: _logoutAlertsFcm,
                  onChanged: (v) => _update('logout_alerts_fcm', v),
                ),
                SwitchListTile(
                  title: Text('تنبيهات النشاط المشبوه'),
                  subtitle: Text('عند اكتشاف سلوك غير طبيعي'),
                  value: _suspiciousAlerts,
                  onChanged: (v) => _update('suspicious_login_alerts', v),
                ),
                SwitchListTile(
                  title: Text('ملخص أمني يومي'),
                  subtitle: Text('ملخص لجميع محاولات الدخول والخروج'),
                  value: _dailySummary,
                  onChanged: (v) => _update('daily_security_summary', v),
                ),
              ],
            ),
    );
  }
}
```

### التحقق من التفضيل قبل الإرسال

```php
// في LogoutService
private function shouldSendNotification(User $user, string $channel): bool
{
    $prefs = $user->notificationPreferences
             ?? UserNotificationPreference::defaults();

    return match ($channel) {
        'email' => $prefs->logout_alerts_email,
        'fcm'   => $prefs->logout_alerts_fcm,
        default => true,
    };
}
```

## 4. إشعار عند Logout من جميع الأجهزة

عند استخدام ميزة "تسجيل الخروج من جميع الأجهزة"، يجب تأكيد العملية الحساسة:

```php
// في AuthService::logoutFromAllDevices()
public function logoutFromAllDevices(User $user): int
{
    $count = $user->tokens()->count();
    $user->tokens()->delete();
    $user->update(['fcm_token' => null]);

    Log::warning('تسجيل خروج من جميع الأجهزة', [
        'user_id' => $user->id,
        'devices' => $count,
        'ip'      => request()->ip(),
    ]);

    // إرسال إشعار تأكيد للمستخدم
    if ($user->fcm_token) {
        $user->notify(new AllDevicesLoggedOut($count));
    }

    return $count;
}
```

```php
<?php
// app/Notifications/AllDevicesLoggedOut.php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AllDevicesLoggedOut extends Notification
{
    public function __construct(
        private readonly int $devicesCount,
    ) {}

    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم تسجيل الخروج من جميع الأجهزة')
            ->line("تم تسجيل الخروج من {$this->devicesCount} جهاز.")
            ->line('إذا لم تكن أنت من نفذ هذه العملية، حسابك قد يكون مخترقاً.')
            ->action('تأمين الحساب', url('/security/change-password'));
    }
}
```

## 5. توصيات أمنية للمستخدمين

| التوصية | التفصيل |
|---------|---------|
| تفعيل الإشعارات الأمنية | تأكد من تفعيل إشعارات تسجيل الخروج في الإعدادات |
| مراجعة الأجهزة المتصلة | تحقق من قائمة الأجهزة النشطة دورياً |
| تغيير كلمة المرور فوراً | عند استلام تنبيه بتسجيل خروج غير متوقع |
| عدم مشاركة الجهاز | استخدم حساب ضيف عند الحاجة |
| تسجيل الخروج يدوياً | لا تغلق التطبيق مباشرة — استخدم زر تسجيل الخروج |

## 6. الحالات الطرفية (Edge Cases)

| المشكلة | المعالجة |
|---------|----------|
| إشعارات متكررة من نفس الجهاز | تجاهل الإشعار إذا كان الجهاز معروفاً خلال 5 دقائق |
| إيقاف الإشعارات نهائياً | لا يمكن تعطيل إشعار تغيير كلمة المرور أبداً |
| مستخدم حذف البريد الإلكتروني | إرسال عبر FCM فقط |
| FCM token منتهي | حذف التوكن والصمت دون خطأ |
| عدة محاولات خروج سريعة | دمج الإشعارات في إشعار واحد خلال 60 ثانية |
