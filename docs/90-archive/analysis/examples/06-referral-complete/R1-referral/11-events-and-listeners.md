# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: ReferralClaimed

```php
<?php
// app/Events/ReferralClaimed.php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReferralClaimed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $referrer,
        public readonly User $referred,
        public readonly string $code,
    ) {}
}
```

## Listener: SendReferralReward

```php
<?php
// app/Listeners/SendReferralReward.php

namespace App\Listeners;

use App\Events\ReferralClaimed;
use App\Services\RewardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendReferralReward implements ShouldQueue
{
    public function __construct(
        private readonly RewardService $rewardService
    ) {}

    public function handle(ReferralClaimed $event): void
    {
        try {
            // سيتم صرف المكافأة لاحقاً عند أول معاملة للمدعو
            // هذا المستمع للإشعار فقط
            $event->referrer->notify(new \App\Notifications\ReferralCodeUsed(
                referredName: $event->referred->name,
            ));
        } catch (\Throwable $e) {
            Log::warning('فشل إشعار الإحالة', [
                'referrer_id' => $event->referrer->id,
            ]);
        }
    }
}
```

## Event: RewardPaid (لصرف المكافأة)

```php
<?php
// app/Events/RewardPaid.php

class RewardPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $referrer,
        public readonly User $referred,
        public readonly float $referrerAmount,
        public readonly float $referredAmount,
    ) {}
}
```
