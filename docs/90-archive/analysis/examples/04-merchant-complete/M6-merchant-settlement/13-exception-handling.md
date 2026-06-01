# 13 - معالجة الاستثناءات (Exception Handling) للتسوية

## نظرة عامة
نظام معالجة الاستثناءات في التسوية البنكية يضمن التعامل مع جميع حالات الفشل المحتملة أثناء عملية تحويل الأموال من محفظة Beza إلى الحساب البنكي للتاجر. كل استثناء له رسالة واضحة وآلية استرداد محددة.

```php
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

// ===== استثناءات التسوية البنكية =====

class SettlementFailedException extends Exception
{
    public function __construct(
        public readonly int $settlementId,
        ?string $reason = null
    ) {
        parent::__construct(
            $reason ?? "فشلت عملية التسوية رقم {$settlementId}",
            500
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'SETTLEMENT_FAILED',
            'data'    => [
                'settlement_id' => $this->settlementId,
            ],
        ], 500);
    }
}

class BankTransferFailedException extends Exception
{
    public function __construct(
        public readonly int $settlementId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly ?string $bankErrorCode = null,
        public readonly ?string $bankErrorMessage = null
    ) {
        $message = "فشل التحويل البنكي للتسوية {$settlementId}: {$bankErrorMessage}";
        parent::__construct($message, 502);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'فشل التحويل البنكي. يرجى المحاولة لاحقاً.',
            'code'    => 'BANK_TRANSFER_FAILED',
            'data'    => [
                'settlement_id' => $this->settlementId,
                'amount'        => $this->amount,
                'currency'      => $this->currency,
                'bank_error'    => $this->bankErrorMessage,
            ],
        ], 502);
    }

    /**
     * تسجيل الخطأ في سجل الأخطاء للرجوع إليه
     */
    public function report(): void
    {
        Log::channel('bank_transfers')->error($this->getMessage(), [
            'settlement_id'  => $this->settlementId,
            'amount'         => $this->amount,
            'currency'       => $this->currency,
            'bank_error_code' => $this->bankErrorCode,
        ]);
    }
}

class InsufficientMerchantBalanceException extends Exception
{
    public function __construct(
        public readonly float $required,
        public readonly float $available,
        public readonly string $currency
    ) {
        parent::__construct(
            "رصيد غير كافٍ للتسوية. المطلوب: {$required} {$currency}، المتوفر: {$available} {$currency}",
            422
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
        ], 422);
    }
}

class MinimumSettlementNotMetException extends Exception
{
    public function __construct(
        public readonly float $minimum,
        public readonly string $currency
    ) {
        parent::__construct(
            "الحد الأدنى للتسوية هو {$minimum} {$currency}. يرجى تجميع مبلغ أكبر.",
            422
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'MINIMUM_SETTLEMENT_NOT_MET',
            'data'    => [
                'minimum'  => $this->minimum,
                'currency' => $this->currency,
            ],
        ], 422);
    }
}

class PendingSettlementExistsException extends Exception
{
    public function __construct()
    {
        parent::__construct('لديك طلب تسوية معلق بالفعل. يرجى الانتظار حتى اكتماله.', 422);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'PENDING_SETTLEMENT_EXISTS',
        ], 422);
    }
}

class BankAccountNotActiveException extends Exception
{
    public function __construct()
    {
        parent::__construct('الحساب البنكي غير نشط. يرجى التحقق من بيانات حسابك.', 422);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'BANK_ACCOUNT_NOT_ACTIVE',
        ], 422);
    }
}

class SettlementFrequencyExceededException extends Exception
{
    public function __construct()
    {
        parent::__construct('لقد تجاوزت الحد الأقصى لطلبات التسوية اليومية. يمكنك طلب تسوية جديدة بعد 24 ساعة.', 429);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'SETTLEMENT_FREQUENCY_EXCEEDED',
        ], 429);
    }
}

class InvalidBankAccountException extends Exception
{
    public function __construct(string $details)
    {
        parent::__construct("بيانات الحساب البنكي غير صحيحة: {$details}", 422);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'INVALID_BANK_ACCOUNT',
        ], 422);
    }
}

class SettlementAlreadyProcessedException extends Exception
{
    public function __construct(int $settlementId)
    {
        parent::__construct("طلب التسوية {$settlementId} تمت معالجته مسبقاً", 409);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'SETTLEMENT_ALREADY_PROCESSED',
        ], 409);
    }
}
```

## آلية استرداد فشل التحويل البنكي

```php
<?php

namespace App\Services;

use App\Events\SettlementFailed;
use App\Exceptions\BankTransferFailedException;
use App\Models\MerchantSettlement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlementRetryHandler
{
    /**
     * معالجة فشل التحويل البنكي وإعادة المحاولة
     *
     * الخطوات:
     * 1. تسجيل الفشل مع رمز الخطأ من البنك
     * 2. إلغاء تجميد الرصيد في المحفظة (إعادته)
     * 3. إذا كانت أول محاولة: إعادة المحاولة بعد ساعة
     * 4. إذا كانت المحاولة الثانية: إعادة المحاولة بعد 24 ساعة
     * 5. بعد 3 محاولات: إيقاف وإبلاغ فريق الدعم
     * 6. إشعار التاجر في كل محاولة فاشلة
     */
    public function handleFailedBankTransfer(
        MerchantSettlement $settlement,
        string $bankErrorCode,
        ?string $bankErrorMessage
    ): void {
        $attemptKey = "settlement-retry-{$settlement->id}";
        $retryCount = (int) Cache::get($attemptKey, 0);

        if ($retryCount >= 3) {
            $this->escalateToSupport($settlement, $bankErrorCode, $bankErrorMessage);
            return;
        }

        // إعادة الرصيد المجمد إلى المحفظة
        DB::transaction(function () use ($settlement) {
            $wallet = $settlement->merchant->wallet($settlement->currency);
            $wallet->increment('balance', $settlement->net_amount);
            $wallet->decrement('frozen_balance', $settlement->net_amount);
        });

        // إطلاق حدث الفشل (للمستمعين)
        event(new SettlementFailed($settlement, $bankErrorMessage, $bankErrorCode));

        // جدولة إعادة المحاولة
        $retryCount++;
        Cache::put($attemptKey, $retryCount, now()->addDays(3));

        $delays = [
            1 => now()->addHour(),
            2 => now()->addDay(),
            3 => now()->addDays(3),
        ];

        $retryJob = new \App\Jobs\RetrySettlementBankTransfer($settlement);
        dispatch($retryJob->delay($delays[$retryCount]));

        Log::warning("إعادة محاولة التسوية {$settlement->id} - المحاولة {$retryCount}");
    }

    /**
     * تصعيد الأمر لفريق الدعم بعد 3 محاولات فاشلة
     */
    private function escalateToSupport(
        MerchantSettlement $settlement,
        string $bankErrorCode,
        ?string $bankErrorMessage
    ): void {
        $settlement->update([
            'status'         => 'failed',
            'failure_reason' => "فشل بعد 3 محاولات. رمز الخطأ: {$bankErrorCode} - {$bankErrorMessage}",
        ]);

        // إشعار فريق الدعم للتدخل اليدوي
        \Illuminate\Support\Facades\Notification::route('mail', config('beza.support_email'))
            ->notify(new \App\Notifications\SettlementNeedsReviewNotification($settlement));

        Log::critical("تسوية {$settlement->id} تحتاج تدخل يدوي - فشل التحويل البنكي بعد 3 محاولات");
    }
}
```

## جدول ملخص الاستثناءات

| الاستثناء | كود HTTP | رمز الخطأ | الحالة أدت إليه |
|-----------|---------|-----------|----------------|
| BankTransferFailedException | 502 | BANK_TRANSFER_FAILED | فشل API البنك |
| InsufficientMerchantBalanceException | 422 | INSUFFICIENT_BALANCE | رصيد المحفظة < المطلوب |
| MinimumSettlementNotMetException | 422 | MINIMUM_SETTLEMENT_NOT_MET | المبلغ < 50 USD |
| PendingSettlementExistsException | 422 | PENDING_SETTLEMENT_EXISTS | طلب سابق معلق |
| BankAccountNotActiveException | 422 | BANK_ACCOUNT_NOT_ACTIVE | حساب بنكي موقف |
| SettlementFrequencyExceededException | 429 | SETTLEMENT_FREQUENCY_EXCEEDED | أكثر من تسوية/يوم |
| InvalidBankAccountException | 422 | INVALID_BANK_ACCOUNT | IBAN/رقم حساب خاطئ |
| SettlementAlreadyProcessedException | 409 | SETTLEMENT_ALREADY_PROCESSED | محاولة معالجة مكررة |
