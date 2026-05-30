<?php

declare(strict_types=1);

namespace Modules\Settlement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\DTOs\PostingResultDto;
use Modules\CoreFinancialEngine\Services\PostingEngine;
use Modules\Settlement\DTOs\CreateSettlementDto;
use Modules\Settlement\DTOs\SettlementResultDto;
use Modules\Settlement\Exceptions\SettlementAlreadyCompletedException;
use Modules\Settlement\Exceptions\SettlementNotFoundException;
use Modules\Settlement\Models\Settlement;
use Modules\Settlement\Models\SettlementLine;
use Modules\Settlement\Repositories\SettlementRepository;
use Illuminate\Support\Str;

final class SettlementService
{
    private const STATE_FLOW = [
        'pending' => ['processing', 'failed'],
        'processing' => ['completed', 'failed'],
        'completed' => ['retry'],
        'failed' => ['retry', 'pending'],
        'retry' => ['processing', 'failed'],
    ];

    public function __construct(
        private readonly SettlementRepository $settlements,
        private readonly PostingEngine $posting,
    ) {}

    public function create(CreateSettlementDto $dto): Settlement
    {
        $settlement = new Settlement();
        $settlement->id = Str::ulid()->toBase32();
        $settlement->reference_type = $dto->referenceType;
        $settlement->reference_id = $dto->referenceId;
        $settlement->settlement_type = $dto->settlementType;
        $settlement->status = 'pending';
        $settlement->gross_amount = $dto->grossAmount;
        $settlement->fee_amount = $dto->feeAmount;
        $settlement->commission_amount = $dto->commissionAmount;
        $settlement->net_amount = $dto->grossAmount - $dto->feeAmount - $dto->commissionAmount;
        $settlement->currency = $dto->currency;
        $settlement->settlement_account_id = $dto->settlementAccountId;
        $settlement->period_start = $dto->periodStart;
        $settlement->period_end = $dto->periodEnd;
        $settlement->metadata = $dto->metadata;

        return $this->settlements->save($settlement);
    }

    public function execute(string $settlementId): SettlementResultDto
    {
        $settlement = $this->findOrFail($settlementId);
        $this->transitionState($settlement, 'processing');

        try {
            $lines = $this->buildPostingLines($settlement);

            $instruction = new PostingInstructionDto(
                referenceType: "settlement_{$settlement->reference_type}",
                referenceId: $settlement->reference_id,
                description: "Settlement {$settlement->settlement_type}: {$settlement->id}",
                lines: $lines,
                channel: 'system',
                metadata: $settlement->metadata,
            );

            $result = $this->posting->execute($instruction);

            if ($result->success) {
                $settlement->status = 'completed';
                $settlement->cfe_transaction_id = $result->transactionId;
                $settlement->settled_at = now();
                $this->settlements->save($settlement);
            }

            return new SettlementResultDto(
                success: $result->success,
                settlementId: $settlement->id,
                netAmount: $settlement->net_amount,
                cfeTransactionId: $result->transactionId,
                error: $result->errorMessage,
            );
        } catch (\Throwable $e) {
            $this->transitionState($settlement, 'failed');
            Log::error('Settlement execution failed', [
                'settlement_id' => $settlementId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function retry(string $settlementId): SettlementResultDto
    {
        $settlement = $this->findOrFail($settlementId);
        $this->transitionState($settlement, 'retry');
        return $this->execute($settlementId);
    }

    public function addLine(string $settlementId, string $accountId, int $amount, string $type, ?string $description = null): SettlementLine
    {
        $settlement = $this->findOrFail($settlementId);
        if ($settlement->status !== 'pending') {
            throw new SettlementAlreadyCompletedException($settlementId, $settlement->status);
        }

        $line = new SettlementLine();
        $line->id = Str::ulid()->toBase32();
        $line->settlement_id = $settlementId;
        $line->account_id = $accountId;
        $line->amount = $amount;
        $line->type = $type;
        $line->description = $description;
        $line->save();

        return $line;
    }

    /** Process daily cutoff — aggregate all merchant, agent, biller settlements */
    public function processDailyCutoff(): array
    {
        $total = 0;
        $cutoffDate = now()->subDay()->startOfDay();

        DB::transaction(function () use (&$total, $cutoffDate) {
            $merchantSettlements = $this->processMerchantSettlements($cutoffDate);
            $agentSettlements = $this->processAgentSettlements($cutoffDate);
            $billerSettlements = $this->processBillerSettlements($cutoffDate);
            $total = $merchantSettlements + $agentSettlements + $billerSettlements;
        });

        Log::info('Daily settlement cutoff processed', [
            'total_entries' => $total,
            'cutoff_date' => $cutoffDate->toDateString(),
        ]);

        return ['total' => $total, 'cutoff_date' => $cutoffDate->toDateString()];
    }

    /** Reconciliation: compare CFE ledger vs bank statement */
    public function reconcile(string $settlementId, array $bankStatement): array
    {
        $settlement = $this->findOrFail($settlementId);
        $cfeBalance = $settlement->net_amount;
        $bankBalance = (int) ($bankStatement['amount'] ?? 0);
        $difference = $cfeBalance - $bankBalance;

        $result = [
            'settlement_id' => $settlementId,
            'cfe_amount' => $cfeBalance,
            'bank_amount' => $bankBalance,
            'difference' => $difference,
            'matched' => abs($difference) <= 1000,
            'currency' => $settlement->currency,
            'reconciled_at' => now()->toIso8601String(),
        ];

        if (!$result['matched']) {
            Log::warning('Settlement reconciliation mismatch', $result);
        }

        return $result;
    }

    public function findByPeriod(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->settlements->findByDateRange($from, $to)->toArray();
    }

    public function getSummary(string $settlementId): array
    {
        $settlement = $this->findOrFail($settlementId);
        $settlement->load('lines');
        return $settlement->toArray();
    }

    public function listByStatus(string $status, int $perPage = 15): \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection
    {
        return $this->settlements->findByStatus($status, $perPage);
    }

    private function processMerchantSettlements(\DateTimeInterface $cutoffDate): int
    {
        $merchants = \Modules\Merchant\Models\Merchant::where('status', 'approved')->get();
        $count = 0;

        foreach ($merchants as $merchant) {
            $volume = \Modules\Merchant\Models\MerchantTransaction::where('merchant_id', $merchant->id)
                ->where('status', 'completed')
                ->where('created_at', '>=', $cutoffDate)
                ->sum('amount');

            if ($volume > 0) {
                $dto = new CreateSettlementDto(
                    referenceType: 'merchant',
                    referenceId: $merchant->id,
                    settlementType: 'merchant_daily',
                    grossAmount: (int) $volume,
                    feeAmount: 0,
                    commissionAmount: 0,
                    currency: 'SYP',
                    settlementAccountId: '10006-001',
                    periodStart: $cutoffDate,
                    periodEnd: now(),
                    metadata: ['merchant_name' => $merchant->shop_name],
                );
                $settlement = $this->create($dto);
                $this->execute($settlement->id);
                $count++;
            }
        }

        return $count;
    }

    private function processAgentSettlements(\DateTimeInterface $cutoffDate): int
    {
        $agents = \Modules\Agent\Models\Agent::where('status', 'approved')->get();
        $count = 0;

        foreach ($agents as $agent) {
            $cashInVolume = \Modules\Agent\Models\AgentTransaction::where('agent_id', $agent->id)
                ->where('type', 'cash_in')
                ->where('created_at', '>=', $cutoffDate)
                ->sum('amount');

            $cashOutVolume = \Modules\Agent\Models\AgentTransaction::where('agent_id', $agent->id)
                ->where('type', 'cash_out')
                ->where('created_at', '>=', $cutoffDate)
                ->sum('amount');

            $net = (int) ($cashInVolume - $cashOutVolume);

            if ($net !== 0) {
                $dto = new CreateSettlementDto(
                    referenceType: 'agent',
                    referenceId: $agent->id,
                    settlementType: 'agent_daily',
                    grossAmount: abs($net),
                    feeAmount: 0,
                    commissionAmount: 0,
                    currency: 'SYP',
                    settlementAccountId: '10006-001',
                    periodStart: $cutoffDate,
                    periodEnd: now(),
                    metadata: [
                        'agent_name' => $agent->shop_name,
                        'cash_in' => $cashInVolume,
                        'cash_out' => $cashOutVolume,
                    ],
                );
                $settlement = $this->create($dto);
                $this->execute($settlement->id);
                $count++;
            }
        }

        return $count;
    }

    private function processBillerSettlements(\DateTimeInterface $cutoffDate): int
    {
        $billers = \Modules\Bills\Models\Biller::all();
        $count = 0;

        foreach ($billers as $biller) {
            $volume = \Modules\Bills\Models\BillPayment::where('biller_id', $biller->id)
                ->where('status', 'completed')
                ->where('created_at', '>=', $cutoffDate)
                ->sum('amount');

            if ($volume > 0) {
                $dto = new CreateSettlementDto(
                    referenceType: 'biller',
                    referenceId: $biller->id,
                    settlementType: 'biller_daily',
                    grossAmount: (int) $volume,
                    feeAmount: 0,
                    commissionAmount: 0,
                    currency: 'SYP',
                    settlementAccountId: '10006-001',
                    periodStart: $cutoffDate,
                    periodEnd: now(),
                    metadata: ['biller_name' => $biller->name],
                );
                $settlement = $this->create($dto);
                $this->execute($settlement->id);
                $count++;
            }
        }

        return $count;
    }

    private function buildPostingLines(Settlement $settlement): array
    {
        $lines = [];
        foreach ($settlement->lines as $line) {
            $lines[] = [
                'account_id' => $line->account_id,
                'amount' => $line->amount,
                'type' => $line->type,
                'description' => $line->description ?? $settlement->settlement_type,
            ];
        }

        if (empty($lines) && $settlement->net_amount > 0) {
            $lines[] = [
                'account_id' => $settlement->settlement_account_id ?? '10006-001',
                'amount' => $settlement->net_amount,
                'type' => 'credit',
                'description' => "Settlement: {$settlement->reference_type}",
            ];
        }

        return $lines;
    }

    private function transitionState(Settlement $settlement, string $targetState): void
    {
        $allowed = self::STATE_FLOW[$settlement->status] ?? [];
        if (!in_array($targetState, $allowed, true)) {
            throw new \RuntimeException(
                "Invalid state transition: {$settlement->status} → {$targetState}"
            );
        }
        $settlement->status = $targetState;
        $this->settlements->save($settlement);
    }

    private function findOrFail(string $id): Settlement
    {
        $settlement = $this->settlements->findById($id);
        if (!$settlement) {
            throw new SettlementNotFoundException($id);
        }
        return $settlement;
    }
}
