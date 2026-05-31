# 11 - الأحداث والمستمعون (Events & Listeners)

## لا يحتاج حدث خاص

عملية تسجيل الخروج بسيطة — حذف التوكن من DB. لا يحتاج Event/Listener.

## اختيارياً — Event: UserLoggedOut

إذا أردت تسجيل أو إرسال إشعار عند الخروج:

```php
<?php
// app/Events/UserLoggedOut.php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedOut
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}
}
```

## Listener: LogLogout

```php
<?php
// app/Listeners/LogLogout.php

namespace App\Listeners;

use App\Events\UserLoggedOut;
use Illuminate\Support\Facades\Log;

class LogLogout
{
    public function handle(UserLoggedOut $event): void
    {
        Log::info('تسجيل خروج', [
            'user_id' => $event->user->id,
            'time'    => now()->toIso8601String(),
        ]);
    }
}
```

## تسجيل (اختياري)

```php
<?php
// app/Providers/EventServiceProvider.php

protected $listen = [
    UserLoggedOut::class => [
        LogLogout::class,
    ],
];
```

## متى قد تحتاج حدث للخروج؟

| السيناريو | الفائدة |
|-----------|---------|
| إبطال التوكنات عبر WebSocket | إغلاق الاتصالات المباشرة |
| تسجيل للمراجعة (Audit Log) | متابعة نشاط المستخدم |
| إلغاء الأجهزة المرتبطة | إذا كان لديك أجهزة مرتبطة |
