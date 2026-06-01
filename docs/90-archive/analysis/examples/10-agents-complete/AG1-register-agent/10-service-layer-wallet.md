# 10 - طبقة خدمة المحفظة (Wallet Service) - الوكلاء

## خدمة محفظة الوكيل

```php
<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentWallet;
use App\Models\AgentTransaction;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\DailyLimitExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\AgentCashIn;
use App\Events\AgentCashOut;

class AgentWalletService
{
    private const COMMISSION_PRECISION = 3;
    private const MAX_DECIMAL = 15;
    private const SCALE = 2;

    public function __construct(
        private AgentTransactionService $transactionService,
    ) {}

    /**
     * إنشاء محفظة جديدة للوكيل
     */
    public function createWalletForAgent(Agent $agent): AgentWallet
    {
        return DB::transaction(function () use ($agent) {
            $wallet = AgentWallet::create([
                'agent_id' => $agent->id,
                'balance' => 0.00,
                'pending_balance' => 0.00,
                'total_commission_earned' => 0.00,
                'total_cash_in' => 0.00,
                'total_cash_out' => 0.00,
                'currency' => 'SAR',
            ]);

            Log::info('تم إنشاء محفظة للوكيل', [
                'agent_id' => $agent->id,
                'wallet_id' => $wallet->id,
            ]);

            return $wallet;
        });
    }

    /**
     * إيداع في محفظة الوكيل (Cash In)
     */
    public function cashIn(
        Agent $agent,
        float $amount,
        ?int $userId = null,
        string $description = '',
    ): AgentTransaction {
        $this->validateDailyLimit($agent, $amount);

        return DB::transaction(function () use ($agent, $amount, $userId, $description) {
            $wallet = $agent->wallet()->lockForUpdate()->firstOrFail();
            $balanceBefore = $wallet->balance;

            $commission = $this->calculateCommission($amount, $agent->commission_rate);
            $netAmount = $amount - $commission;

            $wallet->increment('balance', $netAmount);
            $wallet->increment('total_cash_in', $amount);
            $wallet->increment('total_commission_earned', $commission);

            $transaction = $this->transactionService->record([
                'agent_id' => $agent->id,
                'type' => 'cash_in',
                'user_id' => $userId,
                'amount' => $amount,
                'commission' => $commission,
                'net_amount' => $netAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'currency' => 'SAR',
                'status' => 'completed',
            ]);

            $agent->increment('current_daily_total', $amount);

            event(new AgentCashIn($agent, $transaction));

            return $transaction;
        });
    }

    /**
     * سحب من محفظة الوكيل (Cash Out)
     */
    public function cashOut(
        Agent $agent,
        float $amount,
        ?int $userId = null,
        string $description = '',
    ): AgentTransaction {
        return DB::transaction(function () use ($agent, $amount, $userId, $description) {
            $wallet = $agent->wallet()->lockForUpdate()->firstOrFail();

            if ($wallet->balance < $amount) {
                throw new InsufficientBalanceException(
                    $wallet->balance,
                    $amount,
                    $agent->id
                );
            }

            $balanceBefore = $wallet->balance;

            $wallet->decrement('balance', $amount);
            $wallet->increment('total_cash_out', $amount);

            $transaction = $this->transactionService->record([
                'agent_id' => $agent->id,
                'type' => 'cash_out',
                'user_id' => $userId,
                'amount' => $amount,
                'commission' => 0,
                'net_amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'currency' => 'SAR',
                'status' => 'completed',
            ]);

            event(new AgentCashOut($agent, $transaction));

            return $transaction;
        });
    }

    /**
     * حساب العمولة
     */
    public function calculateCommission(float $amount, float $rate): float
    {
        $commission = $amount * $rate;
        return round($commission, self::COMMISSION_PRECISION);
    }

    /**
     * التحقق من الرصيد
     */
    public function checkBalance(Agent $agent): array
    {
        $wallet = $agent->wallet()->firstOrFail();
        $dailyUsed = $agent->current_daily_total;

        return [
            'balance' => (float) $wallet->balance,
            'pending_balance' => (float) $wallet->pending_balance,
            'available_balance' => (float) ($wallet->balance - $wallet->pending_balance),
            'daily_limit' => (float) $agent->max_daily_limit,
            'daily_used' => (float) $dailyUsed,
            'daily_remaining' => (float) ($agent->max_daily_limit - $dailyUsed),
            'currency' => $wallet->currency,
        ];
    }

    /**
     * التحقق من الحد اليومي
     */
    private function validateDailyLimit(Agent $agent, float $amount): void
    {
        $this->resetDailyTotalIfNeeded($agent);

        $projectedTotal = $agent->current_daily_total + $amount;
        if ($projectedTotal > $agent->max_daily_limit) {
            throw new DailyLimitExceededException(
                $agent->max_daily_limit,
                $agent->current_daily_total,
                $amount,
                $agent->id
            );
        }
    }

    /**
     * إعادة تعيين المجموع اليومي إذا كان يوم جديد
     */
    private function resetDailyTotalIfNeeded(Agent $agent): void
    {
        $lastReset = $agent->last_daily_reset_at;
        $today = now()->startOfDay();

        if (!$lastReset || $lastReset->lessThan($today)) {
            $agent->update([
                'current_daily_total' => 0,
                'last_daily_reset_at' => now(),
            ]);
        }
    }
}
```

## خدمة تسجيل المعاملات

```php
<?php

namespace App\Services;

use App\Models\AgentTransaction;
use Illuminate\Support\Facades\Log;

class AgentTransactionService
{
    public function record(array $data): AgentTransaction
    {
        $transaction = AgentTransaction::create($data);

        Log::info('تم تسجيل معاملة وكيل', [
            'agent_id' => $data['agent_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'transaction_id' => $transaction->id,
        ]);

        return $transaction;
    }
}
```

## ملخص العمليات

| العملية | الوصف | تأثير الرصيد | العمولة |
|---------|-------|-------------|---------|
| Cash In | إيداع أموال في محفظة الوكيل | يزيد الرصيد | تُخصم من المبلغ |
| Cash Out | سحب أموال من محفظة الوكيل | يقل الرصيد | لا توجد |
| Commission | حساب عمولة الوكيل على المعاملة | تزيد أرباح العمولة | نسبة مئوية |
| Balance Check | التحقق من الرصيد والحدود اليومية | لا تأثير | — |
