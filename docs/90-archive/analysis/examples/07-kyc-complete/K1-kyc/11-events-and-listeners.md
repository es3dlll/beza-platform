# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: KycUpdated

```php
<?php
// app/Events/KycUpdated.php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KycUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User   $user,
        public readonly string $newStatus,
        public readonly ?string $rejectionReason = null,
    ) {}
}
```

## Listener: SendKycNotification

```php
<?php
// app/Listeners/SendKycNotification.php

namespace App\Listeners;

use App\Events\KycUpdated;
use App\Notifications\KycStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendKycNotification implements ShouldQueue
{
    public function handle(KycUpdated $event): void
    {
        try {
            $event->user->notify(new KycStatusChanged(
                status:  $event->newStatus,
                reason:  $event->rejectionReason,
            ));

            // إذا كانت الحالة pending → إشعار للمشرفين
            if ($event->newStatus === 'pending') {
                $admins = \App\Models\User::where('is_admin', true)->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\KycPendingReview(
                        user: $event->user,
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('فشل إشعار KYC', [
                'user_id' => $event->user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
```
