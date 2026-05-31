<?php

declare(strict_types=1);

namespace App\Modules\Agent\Services;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use App\Modules\Agent\Events\CashInCompleted;
use App\Modules\Agent\Events\CashOutCompleted;
use App\Modules\Agent\Exceptions\DailyLimitExceededException;
use App\Modules\Agent\Exceptions\InsufficientFloatException;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentTransaction;
use App\Modules\Agent\Models\AgentWallet;
use App\Modules\FinancialCore\Services\Engines\PostingEngine;
use App\Modules\FinancialCore\Services\IdempotencyService;
use App\Modules\Ledger\Models\LedgerAccount;
use Illuminate\Support\Facades\DB;

final class CashInOutService
{
    public function __construct(
        private readonly PostingEngine $postingEngine,
        private readonly CommissionService $commissionService,
        private readonly IdempotencyService $idempotencyService,
        private readonly AgentService $agentService,
    ) {}

    public function cashIn(
        string $agentId,
        string $customerWalletId,
        int $amount,
        string $currency = 'SYP',
        ?string $idempotencyKey = null,
        ?float $locationLat = null,
        ?float $locationLng = null,
        ?string $customerPhone = null,
        ?string $customerName = null,
    ): array {
        $this->agentService->assertCanTransact($agentId);
        $agent = $this->agentService->getAgent($agentId);
        $wallet = $this->agentService->getWallet($agentId, $currency);

        if (!$wallet->withinDailyLimit($amount)) {
            throw new DailyLimitExceededException();
        }

        if ($idempotencyKey !== null) {
            $cached = $this->idempotencyService->checkOrCreate($idempotencyKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $commission = $this->commissionService->calculateCommission('cash_in', new Money($amount, Currency::from($currency)), $agent->kyc_tier);

        $result = DB::transaction(function () use ($agent, $wallet, $customerWalletId, $amount, $currency, $idempotencyKey, $locationLat, $locationLng, $customerPhone, $customerName, $commission) {
            $postResult = $this->postingEngine->execute(
                fromWalletId: $agent->id,
                toWalletId: $customerWalletId,
                amount: new Money($amount, Currency::from($currency)),
                description: 'Cash in via agent',
                descriptionAr: 'إيداع نقدي عبر الوكيل',
                currency: $currency,
                idempotencyKey: $idempotencyKey,
            );

            $agentTx = AgentTransaction::create([
                'agent_id' => $agent->id,
                'type' => 'cash_in',
                'status' => 'completed',
                'customer_wallet_id' => $customerWalletId,
                'customer_phone' => $customerPhone,
                'customer_name' => $customerName,
                'amount' => $amount,
                'currency' => $currency,
                'commission_amount' => $commission->amount(),
                'commission_rate_bps' => $commission->amount() > 0 ? (int) round(($commission->amount() * 10000) / $amount) : 0,
                'idempotency_key' => $idempotencyKey,
                'transaction_id' => $postResult['transaction']->id,
                'location_lat' => $locationLat,
                'location_lng' => $locationLng,
                'settlement_date' => now()->addDay()->toDateString(),
            ]);

            $wallet->increment('daily_used', $amount);
            $wallet->increment('monthly_used', $amount);
            $wallet->increment('float_balance', $amount);

            if ($idempotencyKey !== null) {
                $this->idempotencyService->complete($idempotencyKey, $agentTx->id, $agentTx->toArray());
            }

            return ['transaction' => $agentTx, 'posting' => $postResult, 'commission' => $commission];
        });

        event(new CashInCompleted(
            agentTransactionId: $result['transaction']->id,
            agentId: $agentId,
            customerWalletId: $customerWalletId,
            amount: $amount,
            commissionAmount: $commission->amount(),
            transactionId: $result['posting']['transaction']->id,
        ));

        return $result;
    }

    public function cashOut(
        string $agentId,
        string $customerWalletId,
        int $amount,
        string $currency = 'SYP',
        ?string $idempotencyKey = null,
        ?float $locationLat = null,
        ?float $locationLng = null,
        ?string $customerPhone = null,
        ?string $customerName = null,
    ): array {
        $this->agentService->assertCanTransact($agentId);
        $agent = $this->agentService->getAgent($agentId);
        $wallet = $this->agentService->getWallet($agentId, $currency);

        if (!$wallet->withinDailyLimit($amount)) {
            throw new DailyLimitExceededException();
        }

        if (!$wallet->hasSufficientFloat($amount)) {
            throw new InsufficientFloatException("Agent float balance {$wallet->float_balance} is less than requested {$amount}");
        }

        if ($idempotencyKey !== null) {
            $cached = $this->idempotencyService->checkOrCreate($idempotencyKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $commission = $this->commissionService->calculateCommission('cash_out', new Money($amount, Currency::from($currency)), $agent->kyc_tier);

        $result = DB::transaction(function () use ($agent, $wallet, $customerWalletId, $amount, $currency, $idempotencyKey, $locationLat, $locationLng, $customerPhone, $customerName, $commission) {
            $postResult = $this->postingEngine->execute(
                fromWalletId: $customerWalletId,
                toWalletId: $agent->id,
                amount: new Money($amount, Currency::from($currency)),
                description: 'Cash out via agent',
                descriptionAr: 'سحب نقدي عبر الوكيل',
                currency: $currency,
                idempotencyKey: $idempotencyKey,
            );

            $agentTx = AgentTransaction::create([
                'agent_id' => $agent->id,
                'type' => 'cash_out',
                'status' => 'completed',
                'customer_wallet_id' => $customerWalletId,
                'customer_phone' => $customerPhone,
                'customer_name' => $customerName,
                'amount' => $amount,
                'currency' => $currency,
                'commission_amount' => $commission->amount(),
                'commission_rate_bps' => $commission->amount() > 0 ? (int) round(($commission->amount() * 10000) / $amount) : 0,
                'idempotency_key' => $idempotencyKey,
                'transaction_id' => $postResult['transaction']->id,
                'location_lat' => $locationLat,
                'location_lng' => $locationLng,
                'settlement_date' => now()->addDay()->toDateString(),
            ]);

            $wallet->increment('daily_used', $amount);
            $wallet->increment('monthly_used', $amount);
            $wallet->decrement('float_balance', $amount);

            if ($idempotencyKey !== null) {
                $this->idempotencyService->complete($idempotencyKey, $agentTx->id, $agentTx->toArray());
            }

            return ['transaction' => $agentTx, 'posting' => $postResult, 'commission' => $commission];
        });

        event(new CashOutCompleted(
            agentTransactionId: $result['transaction']->id,
            agentId: $agentId,
            customerWalletId: $customerWalletId,
            amount: $amount,
            commissionAmount: $commission->amount(),
            transactionId: $result['posting']['transaction']->id,
        ));

        return $result;
    }

    public function getHistory(string $agentId, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return AgentTransaction::where('agent_id', $agentId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
