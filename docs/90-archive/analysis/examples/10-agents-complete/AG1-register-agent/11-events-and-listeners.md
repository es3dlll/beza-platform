# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: AgentRegistered
```php
<?php
namespace App\Events;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Agent $agent,
        public readonly User $user,
    ) {}
}
```

## Event: AgentVerified
```php
<?php
namespace App\Events;
use App\Models\Agent;
use Illuminate\Foundation\Events\Dispatchable;

class AgentVerified
{
    use Dispatchable;

    public function __construct(
        public readonly Agent $agent,
        public readonly string $status, // approved | rejected
    ) {}
}
```

## Listener: SendAgentApprovalNotification
```php
<?php
namespace App\Listeners;
use App\Events\AgentRegistered;
use App\Events\AgentVerified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendAgentApprovalNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(AgentRegistered $event): void
    {
        try {
            $event->user->notify(new \App\Notifications\AgentRegistrationSubmitted($event->agent));
        } catch (\Throwable $e) {
            Log::error('Agent registration notification failed', [
                'agent_id' => $event->agent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(AgentRegistered $event, \Throwable $exception): void
    {
        Log::critical('SendAgentApprovalNotification failed after 3 attempts', [
            'agent_id' => $event->agent->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

class CreateAgentWallet implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AgentVerified $event): void
    {
        if ($event->status !== 'approved') return;

        \App\Models\Wallet::create([
            'user_id' => $event->agent->user_id,
            'type' => 'agent_commission',
            'currency' => 'SYP',
            'balance' => 0,
        ]);
    }
}
```

## التسجيل (Registration - EventServiceProvider)
```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    AgentRegistered::class => [
        SendAgentApprovalNotification::class,
    ],
    AgentVerified::class => [
        CreateAgentWallet::class,
        \App\Listeners\NotifyAgentVerificationResult::class,
    ],
];
```

## Why Async?
| السبب | التفصيل |
|-------|---------|
| سرعة الاستجابة | المستخدم لا ينتظر إرسال الإشعارات |
| تحمل الأخطاء | فشل الإشعار لا يلغي عملية التسجيل |
| إعادة المحاولة | Queue يعيد المحاولة تلقائياً (3 مرات) |
| قابلية التوسع | يمكن تشغيل عدة workers للمعالجة |
