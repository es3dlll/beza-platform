# 19 - الحالات الحدودية (Edge Cases)

## 1. تسوية أثناء استلام نقدي (Settlement During Cash-In)

**المشكلة**: الوكيل يستلم نقدياً (cash-in) بينما لديه طلب تسوية قيد المعالجة.

**الحل**:
```php
<?php

public function validateNoConcurrentCashIn(int $agentId): void
{
    $pendingCashIn = CashIn::where('agent_id', $agentId)
        ->whereIn('status', ['pending', 'processing'])
        ->exists();

    if ($pendingCashIn) {
        throw new \RuntimeException(
            'لا يمكن تقديم طلب تسوية أثناء وجود عملية استلام نقدي قيد المعالجة.'
        );
    }
}
```

## 2. تأخير العطل المصرفية (Bank Holiday Delays)

**المشكلة**: التحويل المصرفي يتأخر بسبب عطلة نهاية الأسبوع أو العطل الرسمية.

**الحل**:
```php
<?php

public function checkBankingHours(): bool
{
    $now = now();
    $dayOfWeek = $now->dayOfWeek;

    // أيام العمل: الأحد - الخميس
    if ($dayOfWeek === 5 || $dayOfWeek === 6) {
        return false;
    }

    // ساعات العمل: 9:00 - 15:00
    $hour = (int) $now->format('H');
    return $hour >= 9 && $hour < 15;
}

public function processSettlementWithDelayCheck(AgentSettlement $settlement): void
{
    if (!$this->checkBankingHours()) {
        $settlement->update([
            'status' => 'pending',
            'notes' => 'خارج أوقات العمل المصرفي. سيتم المعالجة في أول يوم عمل.',
        ]);

        ScheduleSettlementProcessing::dispatch($settlement->id)
            ->onQueue('settlements')
            ->delay(now()->nextWeekday()->setTime(9, 0));
        return;
    }

    $this->walletService->processBankTransfer($settlement->id);
}
```

## 3. التسويات الجزئية (Partial Settlements)

**المشكلة**: الوكيل يريد تسوية جزء من رصيده فقط.

**الحل**:
```php
<?php

public function validatePartialSettlement(int $agentId, float $amount, string $currency): void
{
    $wallet = Wallet::where('user_id', $agentId)
        ->where('currency', $currency)
        ->first();

    if (!$wallet) {
        throw new \RuntimeException('المحفظة غير موجودة.');
    }

    if ($amount > $wallet->balance) {
        throw new \RuntimeException(sprintf(
            'المبلغ المطلوب (%s %s) يتجاوز الرصيد المتاح (%s %s).',
            number_format($amount),
            $currency,
            number_format($wallet->balance),
            $currency
        ));
    }

    if ($amount < $this->getMinimumAmount($currency)) {
        throw new \InvalidArgumentException(
            "الحد الأدنى للتسوية: {$this->getMinimumAmount($currency)} {$currency}"
        );
    }
}

public function processPartialSettlement(int $agentId, float $amount, string $currency): AgentSettlement
{
    $this->validatePartialSettlement($agentId, $amount, $currency);

    return DB::transaction(function () use ($agentId, $amount, $currency) {
        $settlement = AgentSettlement::create([
            'agent_id' => $agentId,
            'amount' => $amount,
            'currency' => $currency,
            'fee' => $this->calculateFee($amount, $currency),
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->walletService->freezeSettlementAmount($agentId, $amount, $currency);

        return $settlement;
    });
}
```

## 4. طلبات تسوية متعددة معلقة (Multiple Pending Requests)

**المشكلة**: الوكيل يرسل عدة طلبات تسوية في وقت واحد.

**الحل**:
```php
<?php

use App\Exceptions\PendingSettlementExistsException;

public function validateMultiplePendingRequests(int $agentId): void
{
    $pendingCount = AgentSettlement::where('agent_id', $agentId)
        ->whereIn('status', ['pending', 'processing'])
        ->count();

    // يمكن تعديل العدد حسب سياسة الشركة
    $maxPending = config('settlement.max_pending_requests', 1);

    if ($pendingCount >= $maxPending) {
        throw new PendingSettlementExistsException(
            "لديك {$pendingCount} طلب تسوية معلق. " .
            "الحد الأقصى هو {$maxPending}. يرجى الانتظار حتى اكتمال طلبك الحالي."
        );
    }
}

// في AgentSettlementService
public function requestSettlement($agent, array $data): array
{
    $this->validateNoConcurrentCashIn($agent->id);
    $this->validateMultiplePendingRequests($agent->id);

    return DB::transaction(function () use ($agent, $data) {
        // ... إنشاء طلب التسوية
    });
}
```

## 5. فشل تحويل مصرفي واسترداد (Bank Transfer Failure Recovery)

**المشكلة**: التحويل المصرفي يفشل بعد خصم الرصيد من المحفظة.

**الحل**: (موجود في `WalletService::processBankTransfer`)
```php
<?php

public function processBankTransferWithRetry(int $settlementId, int $maxRetries = 3): AgentSettlement
{
    $attempt = 0;

    while ($attempt < $maxRetries) {
        try {
            return $this->walletService->processBankTransfer($settlementId);
        } catch (BankTransferFailedException $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                $this->walletService->unfreezeOnFailure(
                    AgentSettlement::findOrFail($settlementId)
                );
                throw $e;
            }
            sleep(pow(2, $attempt)); // Exponential backoff
        }
    }

    throw new BankTransferFailedException('فشل التحويل بعد أقصى عدد من المحاولات.');
}
```

## 6. تداخل التسوية مع الحذف الناعم (Settlement + Soft Delete)

**المشكلة**: الوكيل محذوف ناعماً ولكن لديه طلبات تسوية معلقة.

**الحل**:
```php
<?php

AgentSettlement::whereHas('agent', function ($query) {
    $query->whereNull('deleted_at');
})->where('status', 'pending')->get();
```

## ملخص الحالات الحدودية

| الحالة | المشكلة | الحل |
|--------|---------|------|
| تسوية أثناء cash-in | تعارض العمليات | منع التسوية أثناء وجود cash-in معلق |
| تأخير مصرفي | عطل رسمية | جدولة المعالجة لأول يوم عمل |
| تسوية جزئية | مبلغ أقل من الرصيد الكلي | التحقق من الحد الأدنى |
| طلبات متعددة معلقة | ازدواجية الطلبات | حد أقصى (1-3 طلبات معلقة) |
| فشل تحويل مصرفي | خطأ في API المصرفي | استرداد + إلغاء تجميد + إعادة محاولة |
| حذف الوكيل | مراجع محذوفة | تجاهل الحسابات المحذوفة ناعماً |
