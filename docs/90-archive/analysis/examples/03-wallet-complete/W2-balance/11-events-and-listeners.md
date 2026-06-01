# 11 - الأحداث والمستمعين (Events & Listeners)

## عملية عرض الرصيد هي استعلام — لا تحتاج Events

عرض الرصيد هو مجرد استعلام READ، ولا يحتاج إلى Events أو Listeners.

## لكن Cache يحتاج إبطال عند تغيير الرصيد

عند تنفيذ أي معاملة تغيّر الرصيد (decrement/increment)، يجب مسح Cache.

### في WalletService

```php
// app/Services/WalletService.php

public function decrement(Wallet $wallet, float $amount): void
{
    // ... تنفيذ الخصم
    Cache::forget("balance:user:{$wallet->user_id}");
}

public function increment(Wallet $wallet, float $amount): void
{
    // ... تنفيذ الإضافة
    Cache::forget("balance:user:{$wallet->user_id}");
}
```

### باستخدام Event (مستقبلاً — عند الحاجة لإبطال مركزي)

```php
<?php
// app/Events/BalanceUpdated.php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BalanceUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
    ) {}
}
```

```php
<?php
// app/Listeners/ClearBalanceCache.php

namespace App\Listeners;

use App\Events\BalanceUpdated;
use App\Services\BalanceService;
use Illuminate\Contracts\Queue\ShouldQueue;

class ClearBalanceCache implements ShouldQueue
{
    public function __construct(
        private readonly BalanceService $balanceService
    ) {}

    public function handle(BalanceUpdated $event): void
    {
        $this->balanceService->clearBalanceCache($event->userId);
    }
}
```

```php
// في EventServiceProvider
protected $listen = [
    BalanceUpdated::class => [
        ClearBalanceCache::class,
    ],
];
```

## لماذا لا نحتاج Events لعرض الرصيد؟

| السبب | التفصيل |
|-------|---------|
| READ only | مجرد استعلام لا يغيّر البيانات |
| Cache مباشر | إبطال Cache يتم في Service مباشرة |
| بساطة | لا داعي لتعقيد Event لعملية بسيطة |
| أداء | Events تحتاج Queue → تأخير غير مرغوب |
