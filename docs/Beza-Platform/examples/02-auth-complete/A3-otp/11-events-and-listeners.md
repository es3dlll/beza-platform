# 11 - الأحداث والمستمعون (Events & Listeners)

## Event: OtpGenerated

```php
<?php
// app/Events/OtpGenerated.php

namespace App\Events;

use App\Models\OtpCode;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string  $phone,
        public readonly OtpCode $otp,
    ) {}
}
```

## Event: PhoneVerified

```php
<?php
// app/Events/PhoneVerified.php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhoneVerified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}
}
```

## Listener: LogOtpActivity

```php
<?php
// app/Listeners/LogOtpActivity.php

namespace App\Listeners;

use App\Events\OtpGenerated;
use App\Events\PhoneVerified;
use Illuminate\Support\Facades\Log;

class LogOtpActivity
{
    public function handleOtpGenerated(OtpGenerated $event): void
    {
        Log::info('تم توليد OTP', [
            'phone' => $event->phone,
        ]);
    }

    public function handlePhoneVerified(PhoneVerified $event): void
    {
        Log::info('تم توثيق رقم الهاتف', [
            'user_id' => $event->user->id,
            'phone'   => $event->user->phone,
        ]);
    }

    public function subscribe(\Illuminate\Events\Dispatcher $events): void
    {
        $events->listen(OtpGenerated::class, [self::class, 'handleOtpGenerated']);
        $events->listen(PhoneVerified::class, [self::class, 'handlePhoneVerified']);
    }
}
```

## Listener: UpdateKycAfterPhoneVerified

```php
<?php
// app/Listeners/UpdateKycAfterPhoneVerified.php

namespace App\Listeners;

use App\Events\PhoneVerified;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateKycAfterPhoneVerified implements ShouldQueue
{
    public function handle(PhoneVerified $event): void
    {
        // رفع حالة KYC بعد توثيق الهاتف
        if ($event->user->kyc_status === 'not_submitted') {
            $event->user->update(['kyc_status' => 'pending']);
        }
    }
}
```

## تسجيل الـ Events

```php
<?php
// app/Providers/EventServiceProvider.php

protected $listen = [
    OtpGenerated::class => [],
    PhoneVerified::class => [
        UpdateKycAfterPhoneVerified::class,
    ],
];
```
