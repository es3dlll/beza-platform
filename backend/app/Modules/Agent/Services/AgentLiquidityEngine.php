<?php

declare(strict_types=1);

namespace App\Modules\Agent\Services;

use App\Modules\Agent\Enums\ServicePointStatus;
use App\Modules\Agent\Events\AgentActivated;
use App\Modules\Agent\Events\AgentTransactionCompleted;
use App\Modules\Agent\Events\AgentTransactionValidated;
use App\Modules\Agent\Events\FloatUpdated;
use App\Modules\Agent\Events\TriggerAgentSettlement;
use App\Modules\Agent\Exceptions\InvalidAgentStateException;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentWallet;
use App\Modules\Agent\ValueObjects\AgentId;
use App\Modules\Agent\ValueObjects\CommissionTier;
use App\Modules\Agent\ValueObjects\FloatBalance;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final class AgentLiquidityEngine
{
    public function __construct(
        private readonly AgentService $agentService,
        private readonly CashInOutService $cashInOutService,
        private readonly CommissionService $commissionService,
        private readonly SettlementService $settlementService,
    ) {}

    public function onboard(array $data): Agent
    {
        $agentId = AgentId::generate()->toString();
        $commissionTier = CommissionTier::fromString('Bronze');
        $minimumFloat = 100000;

        $agent = Agent::create(array_merge($data, [
            'id' => $agentId,
            'commission_tier' => 'Bronze',
            'minimum_float' => $minimumFloat,
            'max_txn_limit' => 500000,
            'status' => 'pending',
        ]));

        $this->agentService->activateWallet($agentId);

        Event::dispatch(new AgentActivated(
            agentId: $agentId,
            commissionTier: 'Bronze',
            minimumFloat: $minimumFloat,
            maxTransactionLimit: 500000,
            timestamp: now()->getTimestamp(),
        ));

        return $agent;
    }

    public function validateTransaction(string $agentId, string $type, int $amount): array
    {
        $agent = $this->agentService->getAgent($agentId);
        $wallet = $this->agentService->getWallet($agentId);

        if (!ServicePointStatus::canOperate($agent->status)) {
            throw new InvalidAgentStateException($agentId, $agent->status, 'ACTIVE');
        }

        $float = new FloatBalance(
            available: $wallet->float_balance,
            pending: 0,
            minimumRequired: $agent->minimum_float ?? 100000,
            dailyLimit: $wallet->daily_limit,
            dailyUsed: $wallet->daily_used,
        );

        if ($type === 'CASH_OUT' || $type === 'cash_out') {
            $float->assertSufficient($amount);
        }

        if (!$float->withinDailyLimit($amount)) {
            Event::dispatch(new AgentTransactionValidated(
                agentId: $agentId,
                transactionType: $type,
                amount: $amount,
                currency: 'SYP',
                approved: false,
                reason: 'Daily limit exceeded',
            ));
            return ['approved' => false, 'reason' => 'Daily limit exceeded'];
        }

        if ($float->isBelowMinimum()) {
            Event::dispatch(new LowFloatWarning(
                agentId: $agentId,
                availableBalance: $float->available(),
                minimumRequired: $float->minimumRequired(),
                timestamp: now()->getTimestamp(),
            ));
        }

        Event::dispatch(new AgentTransactionValidated(
            agentId: $agentId,
            transactionType: $type,
            amount: $amount,
            currency: 'SYP',
            approved: true,
        ));

        return ['approved' => true, 'float' => $float->toArray()];
    }

    public function processTransactionCompletion(string $agentId, string $type, int $amount): void
    {
        $wallet = $this->agentService->getWallet($agentId);
        $agent = $this->agentService->getAgent($agentId);

        $previousBalance = $wallet->float_balance;
        $change = ($type === 'cash_in' || $type === 'CASH_IN') ? $amount : -$amount;
        $newBalance = $previousBalance + $change;

        $wallet->update(['float_balance' => $newBalance]);

        Event::dispatch(new FloatUpdated(
            agentId: $agentId,
            newBalance: $newBalance,
            previousBalance: $previousBalance,
            change: $change,
            reason: "Transaction {$type} of {$amount}",
            timestamp: now()->getTimestamp(),
        ));
    }

    public function triggerSettlement(string $agentId, string $date): void
    {
        $settlement = $this->settlementService->generateForAgent(
            $this->agentService->getAgent($agentId),
            $date,
        );

        Event::dispatch(new TriggerAgentSettlement(
            agentId: $agentId,
            settlementDate: $date,
            expectedAmount: $settlement->expected_amount,
            commissionAmount: $settlement->commission_amount,
            timestamp: now()->getTimestamp(),
        ));
    }

    public function getFloatStatus(string $agentId): array
    {
        $agent = $this->agentService->getAgent($agentId);
        $wallet = $this->agentService->getWallet($agentId);

        $float = new FloatBalance(
            available: $wallet->float_balance,
            pending: 0,
            minimumRequired: $agent->minimum_float ?? 100000,
            dailyLimit: $wallet->daily_limit,
            dailyUsed: $wallet->daily_used,
        );

        return $float->toArray();
    }

    public function calculateCommission(string $txnType, int $amount, string $tier): int
    {
        return CommissionTier::fromString($tier)->calculateCommission($txnType, $amount);
    }
}
