# 11 - الأحداث والمستمعين (Events & Listeners) للتسوية

## نظرة عامة
نظام الأحداث يتتبع دورة حياة طلب التسوية البنكية. كل حدث يمثل مرحلة حرجة في العملية، والمستمعون يقومون بالإجراءات المناسبة مثل الإشعارات وتحديث الحسابات وتحديث كشوف البنك.

```php
<?php

namespace App\Events;

use App\Models\MerchantSettlement;
use Illuminate\Foundation\Events\Dispatchable;

// ===== أحداث التسوية البنكية =====

class SettlementRequested
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSettlement $settlement
    ) {}
}

class SettlementProcessing
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSettlement $settlement
    ) {}
}

class SettlementCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSettlement $settlement
    ) {}
}

class SettlementFailed
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSettlement $settlement,
        public readonly string $failureReason,
        public readonly ?string $bankErrorCode = null
    ) {}
}

class SettlementPartiallyCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSettlement $settlement,
        public readonly float $amountTransferred,
        public readonly float $amountRemaining
    ) {}
}

class SettlementCancelled
{
    use Dispatchable;

    public function __construct(
        public readonly MerchantSettlement $settlement,
        public readonly string $cancelledBy
    ) {}
}
```

## المستمعين (Listeners)

```php
<?php

namespace App\Listeners;

use App\Events\SettlementCompleted;
use App\Events\SettlementFailed;
use App\Events\SettlementRequested;
use App\Events\SettlementPartiallyCompleted;
use App\Notifications\SettlementCompletedNotification;
use App\Notifications\SettlementFailedNotification;
use App\Notifications\SettlementRequestedNotification;
use App\Services\BankReconciliationService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

// ===== مستمع طلب التسوية =====
class HandleSettlementRequest
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * عند طلب تسوية جديدة:
     * 1. تجميد الرصيد في المحفظة (حتى لا يتم إنفاقه)
     * 2. إشعار التاجر بأن الطلب قيد المعالجة
     */
    public function handle(SettlementRequested $event): void
    {
        $settlement = $event->settlement;
        $merchant = $settlement->merchant;
        $wallet = $merchant->wallet($settlement->currency);

        // تجميد الرصيد المطلوب للتسوية
        $this->walletService->freeze($wallet, $settlement->net_amount);

        // إشعار التاجر
        Notification::send($merchant, new SettlementRequestedNotification($settlement));

        Log::info("طلب تسوية جديد: {$settlement->id} للتاجر {$merchant->id} - {$settlement->net_amount} {$settlement->currency}");
    }
}

// ===== مستمع إتمام التسوية =====
class HandleSettlementCompletion
{
    /**
     * عند إتمام التسوية بنجاح:
     * 1. تحديث رصيد التاجر (خصم المبلغ المجمد)
     * 2. إرسال إشعار النجاح (FCM + Email + SMS)
     * 3. تحديث كشف الحساب البنكي (Bank Reconciliation)
     */
    public function handle(SettlementCompleted $event): void
    {
        $settlement = $event->settlement;
        $merchant = $settlement->merchant;
        $wallet = $merchant->wallet($settlement->currency);

        // 1. خصم المبلغ المجمد نهائياً من المحفظة
        $this->deductFrozenBalance($wallet, $settlement->net_amount);

        // 2. إشعار التاجر عبر جميع القنوات
        Notification::send($merchant, new SettlementCompletedNotification($settlement));

        // إشعار إضافي لمسؤولي المنصة
        Notification::route('mail', config('beza.finance_email'))
            ->notify(new \App\Notifications\AdminSettlementNotification($settlement));

        // 3. تحديث كشف الحساب البنكي (للرجوع إليه لاحقاً)
        BankReconciliationService::recordSettlement($settlement);

        Log::info("تمت التسوية بنجاح: {$settlement->id} - {$settlement->net_amount} {$settlement->currency}");
    }

    private function deductFrozenBalance($wallet, float $amount): void
    {
        $wallet->decrement('frozen_balance', $amount);
        // السجل في ledger يتم تلقائياً عبر model events
    }
}

// ===== مستمع فشل التسوية =====
class HandleSettlementFailure
{
    /**
     * عند فشل التسوية:
     * 1. إلغاء تجميد الرصيد في المحفظة
     * 2. إرسال إشعار الفشل
     * 3. تسجيل سبب الفشل للرجوع إليه
     */
    public function handle(SettlementFailed $event): void
    {
        $settlement = $event->settlement;
        $merchant = $settlement->merchant;
        $wallet = $merchant->wallet($settlement->currency);

        // 1. إلغاء تجميد الرصيد
        $wallet->increment('balance', $settlement->net_amount);
        $wallet->decrement('frozen_balance', $settlement->net_amount);

        // 2. إشعار التاجر بالفشل
        Notification::send($merchant, new SettlementFailedNotification(
            settlement: $settlement,
            reason: $event->failureReason
        ));

        // 3. تسجيل السبب
        Log::error("فشلت التسوية {$settlement->id}: {$event->failureReason}" . ($event->bankErrorCode ? " (رمز الخطأ: {$event->bankErrorCode})" : ''));

        // إشعار فريق الدعم للتدخل اليدوي
        Notification::route('mail', config('beza.support_email'))
            ->notify(new \App\Notifications\SettlementNeedsReviewNotification($settlement));
    }
}
```

## ربط الأحداث في EventServiceProvider

```php
<?php

namespace App\Providers;

use App\Events\SettlementCompleted;
use App\Events\SettlementFailed;
use App\Events\SettlementRequested;
use App\Listeners\HandleSettlementCompletion;
use App\Listeners\HandleSettlementFailure;
use App\Listeners\HandleSettlementRequest;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class SettlementEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SettlementRequested::class => [
            HandleSettlementRequest::class,
        ],
        SettlementCompleted::class => [
            HandleSettlementCompletion::class,
        ],
        SettlementFailed::class => [
            HandleSettlementFailure::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        // تسجيل المستمعين الإضافيين للتسوية الجزئية
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\SettlementPartiallyCompleted::class,
            \App\Listeners\HandlePartialSettlement::class
        );
    }
}
```

## نقل الأحداث (Job Queue)

```php
// تكوين Queue منفصل للتسوية لضمان عدم تأخير العمليات الأخرى
// config/queue.php
'connections' => [
    'settlement' => [
        'driver' => 'database',
        'table' => 'settlement_jobs',
        'queue' => 'settlement',
        'retry_after' => 180, // 3 دقائق
    ],
],
```
