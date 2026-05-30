<?php

declare(strict_types=1);

namespace Modules\Agent\Services;

use Modules\Agent\Contracts\AgentServiceInterface;
use Modules\Agent\DTOs\RegisterAgentDto;
use Modules\Agent\DTOs\CashInDto;
use Modules\Agent\DTOs\CashOutDto;
use Modules\Agent\Events\AgentRegistered;
use Modules\Agent\Events\AgentApproved;
use Modules\Agent\Events\AgentCashInCompleted;
use Modules\Agent\Events\AgentCashOutCompleted;
use Modules\Agent\Exceptions\AgentNotFoundException;
use Modules\Agent\Exceptions\AgentNotApprovedException;
use Modules\Agent\Exceptions\AgentLimitExceededException;
use Modules\Agent\Exceptions\AgentFloatInsufficientException;
use Modules\Agent\Models\Agent;
use Modules\Agent\Models\AgentTransaction;
use Modules\Agent\Models\AgentCommission;
use Modules\Agent\Repositories\AgentRepository;
use Modules\CoreFinancialEngine\DTOs\FeeAssessmentDto;
use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\Services\PostingEngine;
use Modules\CoreFinancialEngine\Services\FeeEngine;
use Modules\Float\Services\FloatOrchestrator;
use Modules\Wallet\DTOs\DepositDto;
use Modules\Wallet\DTOs\WithdrawDto;
use Modules\Wallet\Services\WalletService;
use Illuminate\Support\Str;

final class AgentService implements AgentServiceInterface
{
    public function __construct(
        private readonly AgentRepository $agents,
        private readonly WalletService $wallets,
        private readonly PostingEngine $posting,
        private readonly FeeEngine $fees,
        private readonly FloatOrchestrator $floatOrchestrator,
    ) {}

    public function register(RegisterAgentDto $dto): Agent
    {
        $agent = new Agent();
        $agent->id = Str::ulid()->toBase32();
        $agent->user_id = $dto->userId;
        $agent->business_name = $dto->businessName;
        $agent->agent_type = $dto->agentType;
        $agent->status = 'pending';
        $agent->governorate = $dto->governorate;
        $agent->city = $dto->city;
        $agent->area = $dto->area;
        $agent->address = $dto->address;
        $agent->latitude = $dto->latitude;
        $agent->longitude = $dto->longitude;
        $agent->coverage_radius = $dto->coverageRadius;
        $agent->liquidity_score = 100;
        $agent->phone = $dto->phone;
        $agent->alt_phone = $dto->altPhone;
        $agent->metadata = $dto->metadata;

        $this->agents->save($agent);

        $this->floatOrchestrator->ensureAgentAccounts($agent->id);

        event(new AgentRegistered(
            agentId: $agent->id,
            userId: $dto->userId,
            businessName: $dto->businessName,
            governorate: $dto->governorate,
        ));

        return $agent;
    }

    public function approve(string $agentId, string $approvedBy): Agent
    {
        $agent = $this->findOrFail($agentId);
        $agent->status = 'approved';
        $agent->approved_at = now();
        $agent->approved_by = $approvedBy;
        $this->agents->save($agent);

        event(new AgentApproved(
            agentId: $agent->id,
            userId: $agent->user_id,
        ));

        return $agent;
    }

    public function cashIn(CashInDto $dto): array
    {
        $agent = $this->findActiveOrFail($dto->agentId);
        $this->checkDailyLimit($agent, 'cash_in', $dto->amount);

        $walletDeposit = new DepositDto(
            walletId: $dto->userWalletId,
            amount: $dto->amount,
            currency: $dto->currency,
            referenceType: 'agent_cash_in',
            referenceId: $dto->referenceId ?: Str::ulid()->toBase32(),
            channel: 'agent',
            description: "Cash-in via agent {$agent->business_name}",
            metadata: array_merge($dto->metadata ?? [], ['agent_id' => $agent->id]),
        );

        $wallet = $this->wallets->deposit($walletDeposit);

        $this->floatOrchestrator->processCashIn($agent->id, $dto->amount);

        $commission = $this->calculateCommission($agent, $dto->amount, 'cash_in');
        if ($commission > 0) {
            $this->recordCommission($agent->id, null, $commission, 'cash_in');
        }

        $txn = $this->recordTransaction($agent, $dto, 'cash_in', $commission);

        $this->refreshLiquidityScore($agent);

        event(new AgentCashInCompleted(
            agentId: $agent->id,
            userWalletId: $dto->userWalletId,
            amount: $dto->amount,
            commission: $commission,
            referenceId: $dto->referenceId,
        ));

        return [
            'agent' => $agent,
            'wallet' => $wallet,
            'transaction_id' => $txn->id,
            'commission' => $commission,
        ];
    }

    public function cashOut(CashOutDto $dto): array
    {
        $agent = $this->findActiveOrFail($dto->agentId);
        $this->checkDailyLimit($agent, 'cash_out', $dto->amount);

        $cashFloat = $this->floatOrchestrator->getBalancesForOwner('agent', $agent->id);
        $cashBalance = 0;
        foreach ($cashFloat as $f) {
            if ($f['float_type'] === 'cash') {
                $cashBalance = $f['available'];
                break;
            }
        }

        if ($cashBalance < $dto->amount) {
            throw new AgentFloatInsufficientException($cashBalance, $dto->amount);
        }

        $walletWithdraw = new WithdrawDto(
            walletId: $dto->userWalletId,
            amount: $dto->amount,
            currency: $dto->currency,
            referenceType: 'agent_cash_out',
            referenceId: $dto->referenceId ?: Str::ulid()->toBase32(),
            channel: 'agent',
            description: "Cash-out via agent {$agent->business_name}",
            applyFee: $dto->applyFee,
            metadata: array_merge($dto->metadata ?? [], ['agent_id' => $agent->id]),
        );

        $wallet = $this->wallets->withdraw($walletWithdraw);

        $this->floatOrchestrator->processCashOut($agent->id, $dto->amount);

        $commission = $this->calculateCommission($agent, $dto->amount, 'cash_out');
        if ($commission > 0) {
            $this->recordCommission($agent->id, null, $commission, 'cash_out');
        }

        $txn = $this->recordTransaction($agent, $dto, 'cash_out', $commission);

        $this->refreshLiquidityScore($agent);

        event(new AgentCashOutCompleted(
            agentId: $agent->id,
            userWalletId: $dto->userWalletId,
            amount: $dto->amount,
            commission: $commission,
            referenceId: $dto->referenceId,
        ));

        return [
            'agent' => $agent,
            'wallet' => $wallet,
            'transaction_id' => $txn->id,
            'commission' => $commission,
        ];
    }

    public function getNearby(string $governorate, ?float $lat = null, ?float $lng = null, ?int $radius = null): array
    {
        if ($lat !== null && $lng !== null) {
            return $this->agents->findNearby($lat, $lng, $radius ?? 5000)->toArray();
        }
        return $this->agents->findByGovernorate($governorate)->toArray();
    }

    public function getTodaySummary(string $agentId): array
    {
        $agent = $this->findOrFail($agentId);
        return [
            'agent_id' => $agent->id,
            'business_name' => $agent->business_name,
            'today_cash_in' => $this->agents->todayTotal($agentId, 'cash_in'),
            'today_cash_out' => $this->agents->todayTotal($agentId, 'cash_out'),
            'daily_cash_in_limit' => $agent->daily_cash_in_limit,
            'daily_cash_out_limit' => $agent->daily_cash_out_limit,
        ];
    }

    public function getLiquidityScore(string $agentId): array
    {
        $agent = $this->findOrFail($agentId);
        $score = $this->floatOrchestrator->getLiquidityScore($agentId);
        $score['coverage_radius'] = $agent->coverage_radius;

        return $score;
    }

    public function updateCoverageRadius(string $agentId, int $meters): Agent
    {
        $agent = $this->findOrFail($agentId);
        $agent->coverage_radius = max(100, min($meters, 50000));
        $this->agents->save($agent);

        return $agent;
    }

    private function calculateCommission(Agent $agent, int $amount, string $type): int
    {
        $rate = $agent->commission_rate / 100;
        $commission = (int) round($amount * $rate);
        return min($commission, (int) $agent->max_commission_per_txn);
    }

    private function recordTransaction(Agent $agent, CashInDto|CashOutDto $dto, string $type, int $commission): AgentTransaction
    {
        $txn = new AgentTransaction();
        $txn->id = Str::ulid()->toBase32();
        $txn->agent_id = $agent->id;
        $txn->user_wallet_id = $dto->userWalletId;
        $txn->type = $type;
        $txn->amount = $dto->amount;
        $txn->commission = $commission;
        $txn->currency = $dto->currency;
        $txn->status = 'completed';
        $txn->reference_id = $dto->referenceId;
        $txn->metadata = $dto->metadata;

        return $this->agents->saveTransaction($txn);
    }

    private function recordCommission(string $agentId, ?string $txnId, int $amount, string $type): void
    {
        $commission = new AgentCommission();
        $commission->id = Str::ulid()->toBase32();
        $commission->agent_id = $agentId;
        $commission->agent_transaction_id = $txnId;
        $commission->amount = $amount;
        $commission->type = $type;
        $commission->currency = 'SYP';
        $commission->status = 'pending';
        $commission->save();
    }

    private function findOrFail(string $agentId): Agent
    {
        $agent = $this->agents->findById($agentId);
        if (!$agent) {
            throw new AgentNotFoundException($agentId);
        }
        return $agent;
    }

    private function findActiveOrFail(string $agentId): Agent
    {
        $agent = $this->findOrFail($agentId);
        if (!$agent->isActive()) {
            throw new AgentNotApprovedException($agentId, $agent->status);
        }
        return $agent;
    }

    private function checkDailyLimit(Agent $agent, string $type, int $amount): void
    {
        $limitKey = $type === 'cash_in' ? 'daily_cash_in_limit' : 'daily_cash_out_limit';
        $todayTotal = $this->agents->todayTotal($agent->id, $type);
        $limit = (int) $agent->$limitKey;

        if (($todayTotal + $amount) > $limit) {
            throw new AgentLimitExceededException($type, $limit, $todayTotal, $amount);
        }
    }

    private function refreshLiquidityScore(Agent $agent): void
    {
        $score = $this->floatOrchestrator->getLiquidityScore($agent->id);
        $agent->liquidity_score = $score['liquidity_score'];
        $agent->save();
    }
}
