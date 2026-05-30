<?php

declare(strict_types=1);

namespace Modules\Settlement\Services;

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

        if ($settlement->status !== 'pending') {
            throw new SettlementAlreadyCompletedException($settlementId, $settlement->status);
        }

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
                'account_id' => $settlement->settlement_account_id ?? '9000-000',
                'amount' => $settlement->net_amount,
                'type' => 'credit',
                'description' => "Settlement: {$settlement->reference_type}",
            ];
        }

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

    private function findOrFail(string $id): Settlement
    {
        $settlement = $this->settlements->findById($id);
        if (!$settlement) {
            throw new SettlementNotFoundException($id);
        }
        return $settlement;
    }
}
