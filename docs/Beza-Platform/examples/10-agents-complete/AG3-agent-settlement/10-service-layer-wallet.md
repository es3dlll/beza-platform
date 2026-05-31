# 10 - طبقة الخدمة: WalletService للتسوية

## WalletService

```php
<?php

namespace App\Services;

use App\Models\AgentSettlement;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Exceptions\BankTransferFailedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * تجميد مبلغ التسوية في المحفظة
     */
    public function freezeSettlementAmount(int $agentId, float $amount, string $currency): Wallet
    {
        return DB::transaction(function () use ($agentId, $amount, $currency) {
            $wallet = Wallet::where('user_id', $agentId)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->balance < $amount) {
                throw new \RuntimeException('الرصيد غير كافٍ للتجميد.');
            }

            $wallet->decrement('balance', $amount);
            $wallet->increment('frozen_balance', $amount);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'freeze',
                'amount' => $amount,
                'currency' => $currency,
                'description' => 'تجميد مبلغ لتسوية وكيل',
                'reference_type' => AgentSettlement::class,
            ]);

            Log::info("تم تجميد {$amount} {$currency} للوكيل {$agentId}");

            return $wallet->fresh();
        }, attempts: 3);
    }

    /**
     * معالجة التحويل المصرفي وتحديث الرصيد
     */
    public function processBankTransfer(int $settlementId): AgentSettlement
    {
        return DB::transaction(function () use ($settlementId) {
            $settlement = AgentSettlement::lockForUpdate()->findOrFail($settlementId);

            if ($settlement->status !== 'processing') {
                throw new \RuntimeException('طلب التسوية ليس في حالة معالجة.');
            }

            try {
                // استدعاء API المصرفي الخارجي
                $transferResult = $this->callBankApi($settlement);

                if (!$transferResult['success']) {
                    throw new BankTransferFailedException(
                        $transferResult['message'] ?? 'فشل التحويل المصرفي.'
                    );
                }

                // تحديث حالة التسوية إلى مكتملة
                $settlement->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                    'transaction_ref' => $transferResult['reference'],
                ]);

                // خصم المبلغ المجمد نهائياً
                $wallet = Wallet::where('user_id', $settlement->agent_id)
                    ->where('currency', $settlement->currency)
                    ->lockForUpdate()
                    ->firstOrFail();

                $wallet->decrement('frozen_balance', $settlement->amount);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'settlement',
                    'amount' => $settlement->amount,
                    'currency' => $settlement->currency,
                    'fee' => $settlement->fee,
                    'description' => "تسوية وكيل - تحويل مصرفي #{$transferResult['reference']}",
                    'reference_type' => AgentSettlement::class,
                    'reference_id' => $settlement->id,
                ]);

                Log::info("تمت تسوية {$settlement->amount} {$settlement->currency} للوكيل {$settlement->agent_id}");

            } catch (\Throwable $e) {
                $settlement->update([
                    'status' => 'failed',
                    'notes' => $settlement->notes . "\nفشل التحويل: " . $e->getMessage(),
                ]);

                // إلغاء التجميد
                $this->unfreezeOnFailure($settlement);

                throw $e;
            }

            return $settlement->fresh();
        }, attempts: 3);
    }

    /**
     * إلغاء تجميد المبلغ عند فشل التسوية
     */
    public function unfreezeOnFailure(AgentSettlement $settlement): void
    {
        DB::transaction(function () use ($settlement) {
            $wallet = Wallet::where('user_id', $settlement->agent_id)
                ->where('currency', $settlement->currency)
                ->lockForUpdate()
                ->firstOrFail();

            $frozenAmount = $settlement->amount;

            $wallet->increment('balance', $frozenAmount);
            $wallet->decrement('frozen_balance', $frozenAmount);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'unfreeze',
                'amount' => $frozenAmount,
                'currency' => $settlement->currency,
                'description' => 'إلغاء تجميد مبلغ - فشل تسوية',
                'reference_type' => AgentSettlement::class,
                'reference_id' => $settlement->id,
            ]);

            $settlement->update([
                'notes' => $settlement->notes . "\nتم إلغاء تجميد المبلغ {$frozenAmount} {$settlement->currency}",
            ]);

            Log::info("تم إلغاء تجميد {$frozenAmount} {$settlement->currency} للوكيل {$settlement->agent_id}");
        }, attempts: 3);
    }

    /**
     * استدعاء API المصرفي (محاكاة)
     */
    private function callBankApi(AgentSettlement $settlement): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withToken(config('services.bank.api_key'))
                ->post(config('services.bank.transfer_url'), [
                    'account' => $settlement->bank_account,
                    'amount' => $settlement->amount,
                    'currency' => $settlement->currency,
                    'recipient' => $settlement->recipient_name,
                    'reference' => 'SET-' . $settlement->id,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'reference' => $response->json('transaction_id'),
                    'message' => 'تم التحويل بنجاح',
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('error', 'فشل الاتصال بالمصرف'),
            ];

        } catch (\Throwable $e) {
            Log::error('فشل الاتصال بالمصرف', [
                'settlement_id' => $settlement->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'تعذر الاتصال بخدمة التحويل المصرفي',
            ];
        }
    }
}
```

## ملخص العمليات

| العملية | الوصف |
|---------|-------|
| freezeSettlementAmount | تجميد المبلغ في المحفظة |
| processBankTransfer | تحويل مصرفي + تحديث الرصيد |
| unfreezeOnFailure | إلغاء التجميد عند الفشل |

## تدفق العملية

```
1. freezeSettlementAmount()
   ├── lockForUpdate() على المحفظة
   ├── decrement(balance)
   └── increment(frozen_balance)

2. processBankTransfer()
   ├── lockForUpdate() على التسوية
   ├── callBankApi()
   ├── update(status=completed)
   ├── decrement(frozen_balance)
   └── إنشاء سجل معاملة

3. unfreezeOnFailure() (عند الخطأ)
   ├── lockForUpdate() على المحفظة
   ├── increment(balance)
   └── decrement(frozen_balance)
```
