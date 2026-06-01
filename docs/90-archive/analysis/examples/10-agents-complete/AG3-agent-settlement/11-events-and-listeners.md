# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: SettlementRequested
```php
<?php
namespace App\Events;
use App\Models\AgentSettlement;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SettlementRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AgentSettlement $settlement,
        public readonly User $user,
    ) {}
}
```

## Event: SettlementProcessed
```php
<?php
namespace App\Events;
use App\Models\AgentSettlement;
use Illuminate\Foundation\Events\Dispatchable;

class SettlementProcessed
{
    use Dispatchable;

    public function __construct(
        public readonly AgentSettlement $settlement,
        public readonly string $status, // approved | paid | rejected
    ) {}
}
```

## Listener: SendSettlementNotification
```php
<?php
namespace App\Listeners;
use App\Events\SettlementRequested;
use App\Events\SettlementProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendSettlementNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(SettlementRequested $event): void
    {
        try {
            $event->user->notify(new \App\Notifications\SettlementSubmitted($event->settlement));
        } catch (\Throwable $e) {
            Log::error('Settlement notification failed', [
                'settlement_id' => $event->settlement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(SettlementRequested $event, \Throwable $exception): void
    {
        Log::critical('SendSettlementNotification failed after 3 attempts', [
            'settlement_id' => $event->settlement->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

class ProcessSettlementTransfer implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(SettlementProcessed $event): void
    {
        if ($event->status !== 'approved') return;

        try {
            // تحويل المبلغ إلى الحساب البنكي للوكيل
            \App\Services\BankTransferService::transfer(
                $event->settlement->amount,
                $event->settlement->bankAccount
            );

            $event->settlement->update(['status' => 'paid', 'paid_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Settlement transfer failed', [
                'settlement_id' => $event->settlement->id,
                'error' => $e->getMessage(),
            ]);
            $event->settlement->update(['status' => 'failed']);
        }
    }

    public function failed(SettlementProcessed $event, \Throwable $exception): void
    {
        Log::critical('ProcessSettlementTransfer failed after 3 attempts', [
            'settlement_id' => $event->settlement->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

## التسجيل (Registration - EventServiceProvider)
```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    SettlementRequested::class => [
        SendSettlementNotification::class,
    ],
    SettlementProcessed::class => [
        ProcessSettlementTransfer::class,
        \App\Listeners\NotifySettlementResult::class,
    ],
];
```

## Why Async?
| السبب | التفصيل |
|-------|---------|
| سرعة الاستجابة | المستخدم لا ينتظر التحويل البنكي |
| تحمل الأخطاء | فشل التحويل لا يلغي الطلب |
| إعادة المحاولة | Queue يعيد المحاولة (3 مرات) للتحويل |
| تتبع الأخطاء | تسجيل الفشل في Log للتدقيق |
