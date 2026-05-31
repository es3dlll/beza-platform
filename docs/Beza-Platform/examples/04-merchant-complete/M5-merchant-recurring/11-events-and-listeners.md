# 11 - الأحداث والمستمعين (Events & Listeners)

## نظرة عامة
نظام الأحداث في Beza يتعامل مع دورة حياة الاشتراك المتكرر. كل حدث يمثل مرحلة محددة، والمستمع يقوم بالإجراء المناسب (إرسال إشعار، تحديث حالة، إعادة محاولة دفع).

```php
<?php

namespace App\Events;

use App\Models\MerchantSubscription;
use Illuminate\Foundation\Events\Dispatchable;

// ===== أحداث الاشتراكات المتكررة =====

class SubscriptionCreated
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSubscription $subscription
    ) {}
}

class SubscriptionActivated
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSubscription $subscription,
        public readonly string $consentMethod // 'sms', 'whatsapp', 'email'
    ) {}
}

class RecurringInvoiceDue
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSubscription $subscription,
        public readonly int $cycleNumber,
        public readonly float $amount,
        public readonly \Carbon\Carbon $dueDate
    ) {}
}

class PaymentCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSubscription $subscription,
        public readonly int $cycleNumber,
        public readonly string $transactionId
    ) {}
}

class PaymentFailed
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSubscription $subscription,
        public readonly int $cycleNumber,
        public readonly string $reason,
        public readonly int $attemptNumber
    ) {}
}

class SubscriptionCancelled
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSubscription $subscription,
        public readonly string $cancelledBy, // 'merchant', 'customer', 'system'
        public readonly ?string $reason
    ) {}
}

class SubscriptionCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSubscription $subscription
    ) {}
}
```

## المستمعين (Listeners)

```php
<?php

namespace App\Listeners;

use App\Events\RecurringInvoiceDue;
use App\Events\PaymentFailed;
use App\Events\PaymentCompleted;
use App\Events\SubscriptionCancelled;
use App\Notifications\RecurringInvoiceDueNotification;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentCompletedNotification;
use App\Services\PaymentRetryService;
use App\Services\SubscriptionStatusService;
use Illuminate\Support\Facades\Notification;

// ===== مستمع الفاتورة المستحقة =====
class SendInvoiceDueNotification
{
    /**
     * إرسال إشعار للعميل قبل 3 أيام من الخصم
     */
    public function handle(RecurringInvoiceDue $event): void
    {
        $sub = $event->subscription;
        $customer = $sub->customer;

        // إرسال إشعار عبر جميع القنوات
        Notification::send($customer, new RecurringInvoiceDueNotification(
            amount: $event->amount,
            dueDate: $event->dueDate,
            subscription: $sub
        ));

        // إرسال SMS تذكيري
        $customer->notifyViaSms(
            "تذكير: سيتم خصم {$event->amount} {$sub->currency} في {$event->dueDate->format('Y-m-d')}"
        );
    }
}

// ===== مستمع فشل الدفع =====
class HandlePaymentFailure
{
    public function __construct(
        private readonly PaymentRetryService $retryService
    ) {}

    /**
     * إعادة محاولة الدفع مع تباعد زمني (Exponential Backoff)
     */
    public function handle(PaymentFailed $event): void
    {
        $sub = $event->subscription;

        // تسجيل محاولة الفشل
        $sub->paymentAttempts()->create([
            'cycle_number'   => $event->cycleNumber,
            'attempt_number' => $event->attemptNumber,
            'status'         => 'failed',
            'reason'         => $event->reason,
        ]);

        // إذا لم تتجاوز المحاولات 3، جدولة إعادة محاولة
        if ($event->attemptNumber < 3) {
            $this->retryService->scheduleRetry($sub, $event->cycleNumber, $event->attemptNumber + 1);
        } else {
            // بعد 3 محاولات فاشلة، إيقاف الاشتراك
            SubscriptionStatusService::markAsFailed($sub);
        }

        // إشعار العميل والتاجر
        $sub->customer->notify(new PaymentFailedNotification($event));
        $sub->merchant->notify(new PaymentFailedNotification($event));
    }
}

// ===== مستمع إتمام الدفع =====
class ProcessPaymentCompletion
{
    public function handle(PaymentCompleted $event): void
    {
        $sub = $event->subscription;

        // تسجيل عملية الدفع
        $sub->charges()->create([
            'cycle_number'    => $event->cycleNumber,
            'amount'          => $sub->amount,
            'transaction_id'  => $event->transactionId,
            'status'          => 'completed',
        ]);

        // تحويل الرصيد لمحفظة التاجر
        $sub->merchant->wallet->increment($sub->amount);

        // إرسال إيصال للعميل
        Notification::send($sub->customer, new PaymentCompletedNotification(
            amount: $sub->amount,
            transactionId: $event->transactionId
        ));
    }
}

// ===== مستمع إلغاء الاشتراك =====
class HandleSubscriptionCancellation
{
    public function handle(SubscriptionCancelled $event): void
    {
        $sub = $event->subscription;

        // إلغاء أي دفعات مجدولة مستقبلية
        $sub->scheduledCharges()->where('status', 'pending')->update(['status' => 'cancelled']);

        // إشعار العميل
        Notification::send($sub->customer, new \App\Notifications\SubscriptionCancelledNotification(
            subscription: $sub,
            reason: $event->reason
        ));
    }
}
```

## تكوين قائمة الانتظار (Queue Configuration)

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // أحداث الاشتراكات المتكررة
        RecurringInvoiceDue::class => [
            SendInvoiceDueNotification::class,
        ],
        PaymentFailed::class => [
            HandlePaymentFailure::class,
        ],
        PaymentCompleted::class => [
            ProcessPaymentCompletion::class,
        ],
        SubscriptionCancelled::class => [
            HandleSubscriptionCancellation::class,
        ],
    ];

    protected function boot(): void
    {
        parent::boot();
        // معالجة الأحداث على queue منفصلة لعدم التأثير على أداء API
        // queue: recurring للاشتراكات، payment للمدفوعات
    }
}
```

## آلية إعادة المحاولة (Retry Logic)

```php
<?php

namespace App\Services;

class PaymentRetryService
{
    /**
     * جدولة إعادة محاولة الدفع مع تباعد زمني تصاعدي
     *
     * المحاولة | التأخير
     * ---------|---------
     * 1        | 1 ساعة
     * 2        | 6 ساعات
     * 3        | 24 ساعة
     */
    public function scheduleRetry(MerchantSubscription $sub, int $cycleNumber, int $attempt): void
    {
        $delays = [
            1 => now()->addHour(),       // ساعة واحدة
            2 => now()->addHours(6),     // 6 ساعات
            3 => now()->addDay(),        // 24 ساعة
        ];

        $retryAt = $delays[$attempt] ?? now()->addDay();

        // جدولة مهمة إعادة المحاولة
        ScheduleRecurringPaymentJob::dispatch($sub, $cycleNumber, $attempt)
            ->delay($retryAt)
            ->onQueue('recurring');
    }
}
```
