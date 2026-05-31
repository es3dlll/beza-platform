# 13 - معالجة الاستثناءات (Exception Handling)

## نظرة عامة
نظام معالجة الاستثناءات في الاشتراكات المتكررة يضمن التعامل بشكل أنيق مع جميع حالات الفشل. كل استثناء له رسالة واضحة بالعربية وآلية معالجة محددة.

```php
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

// ===== استثناءات الاشتراكات المتكررة =====

class SubscriptionNotFoundException extends Exception
{
    public function __construct(?int $subscriptionId = null)
    {
        $message = $subscriptionId
            ? "الاشتراك رقم {$subscriptionId} غير موجود"
            : 'الاشتراك غير موجود';
        parent::__construct($message, 404);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'SUBSCRIPTION_NOT_FOUND',
        ], 404);
    }
}

class SubscriptionAlreadyActiveException extends Exception
{
    public function __construct()
    {
        parent::__construct('الاشتراك نشط بالفعل ولا يمكن إعادة تنشيطه', 422);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'SUBSCRIPTION_ALREADY_ACTIVE',
        ], 422);
    }
}

class CustomerConsentRequiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('موافقة العميل مطلوبة لبدء الاشتراك', 422);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'CUSTOMER_CONSENT_REQUIRED',
        ], 422);
    }
}

class InsufficientBalanceForRecurringException extends Exception
{
    public function __construct(
        public readonly float $required,
        public readonly float $available,
        public readonly string $currency
    ) {
        parent::__construct(
            "رصيد غير كافٍ للاشتراك المتكرر. المطلوب: {$required} {$currency}، المتوفر: {$available} {$currency}",
            402
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'INSUFFICIENT_BALANCE',
            'data'    => [
                'required'  => $this->required,
                'available' => $this->available,
                'currency'  => $this->currency,
            ],
        ], 402);
    }
}

class RecurringPaymentFailedException extends Exception
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly int $cycleNumber,
        public readonly int $attemptNumber,
        public readonly string $failureReason
    ) {
        $messages = [
            'insufficient_balance' => 'رصيد العميل غير كافٍ لإتمام الدفع',
            'wallet_locked'        => 'محفظة العميل مقفلة مؤقتاً، يرجى المحاولة لاحقاً',
            'customer_inactive'    => 'حساب العميل غير نشط',
            'currency_mismatch'    => 'عملة الاشتراك لا تطابق عملة المحفظة',
            'payment_timeout'      => 'انتهت مهلة معالجة الدفع',
        ];

        $message = $messages[$failureReason] ?? 'فشل في معالجة الدفع المتكرر';
        parent::__construct(
            "{$message} (الدورة: {$cycleNumber}، المحاولة: {$attemptNumber})",
            402
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'RECURRING_PAYMENT_FAILED',
            'data'    => [
                'subscription_id' => $this->subscriptionId,
                'cycle_number'    => $this->cycleNumber,
                'attempt_number'  => $this->attemptNumber,
            ],
        ], 402);
    }
}

class MaxCyclesReachedException extends Exception
{
    public function __construct(int $maxCycles)
    {
        parent::__construct("اكتمل الاشتراك بعد {$maxCycles} دورة", 422);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'MAX_CYCLES_REACHED',
        ], 422);
    }
}

class SubscriptionCancellationNotAllowedException extends Exception
{
    public function __construct(string $reason)
    {
        parent::__construct("لا يمكن إلغاء الاشتراك: {$reason}", 422);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'CANCELLATION_NOT_ALLOWED',
        ], 422);
    }
}
```

## آلية إعادة المحاولة (Retry Mechanism)

```php
<?php

namespace App\Services;

use App\Exceptions\RecurringPaymentFailedException;
use App\Events\PaymentFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecurringPaymentRetryHandler
{
    /**
     * معالجة فشل الدفع وإعادة المحاولة
     *
     * استراتيجية التباعد الزمني:
     * - المحاولة 1: بعد ساعة واحدة
     * - المحاولة 2: بعد 6 ساعات
     * - المحاولة 3: بعد 24 ساعة
     * - بعد 3 محاولات فاشلة: إيقاف الاشتراك وإشعار الطرفين
     *
     * قفل متشائم لمنع تكرار المعالجة:
     * Cache::lock("recurring-payment-{$subscriptionId}-{$cycleNumber}", 300)
     */
    public function handleFailedPayment(MerchantSubscription $sub, int $cycleNumber): void
    {
        $lockKey = "recurring-payment-{$sub->id}-{$cycleNumber}";

        $lock = Cache::lock($lockKey, 300); // قفل لمدة 5 دقائق

        if (!$lock->get()) {
            throw new RecurringPaymentFailedException(
                subscriptionId: $sub->id,
                cycleNumber: $cycleNumber,
                attemptNumber: $sub->current_attempt,
                failureReason: 'wallet_locked'
            );
        }

        try {
            event(new PaymentFailed($sub, $cycleNumber, 'insufficient_balance', $sub->current_attempt));
            Log::warning("فشل دورة رقم {$cycleNumber} للاشتراك {$sub->id} - المحاولة {$sub->current_attempt}");
        } finally {
            $lock->release();
        }
    }
}
```
