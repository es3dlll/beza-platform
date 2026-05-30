<?php

namespace Modules\CoreFinancialEngine\Services;

use Modules\CoreFinancialEngine\Contracts\ReversalEngineInterface;
use Modules\CoreFinancialEngine\DTOs\ReversalInstructionDto;
use Modules\CoreFinancialEngine\DTOs\PostingResultDto;
use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\Events\TransactionReversed;
use Modules\Ledger\Repositories\JournalEntryRepository;
use Illuminate\Support\Str;

final class ReversalEngine implements ReversalEngineInterface
{
    public function __construct(
        private readonly PostingEngine $posting,
        private readonly JournalEntryRepository $entries,
    ) {}

    public function reverse(ReversalInstructionDto $dto): PostingResultDto
    {
        $originalEntries = $this->entries->findByReference('transaction', $dto->originalTransactionId);
        if ($originalEntries->isEmpty()) {
            return new PostingResultDto(
                success: false,
                transactionId: $dto->originalTransactionId,
                journalEntryId: '',
                totalAmount: 0,
                currency: 'SYP',
                errorCode: 'ORIGINAL_NOT_FOUND',
                errorMessage: 'Original transaction not found',
            );
        }

        $originalEntry = $originalEntries->first();
        $reversedLines = [];

        foreach ($originalEntry->lines as $line) {
            $reversedLines[] = [
                'account_id' => $line->account_id,
                'amount' => $line->amount,
                'type' => $line->type === 'debit' ? 'credit' : 'debit',
                'description' => "Reversal: {$dto->reason}",
            ];
        }

        $instruction = new PostingInstructionDto(
            referenceType: 'reversal',
            referenceId: Str::ulid()->toBase32(),
            description: "Reversal of {$dto->originalTransactionId}: {$dto->reason}",
            lines: $reversedLines,
            channel: 'system',
            initiatedBy: $dto->initiatedBy,
            metadata: [
                'original_transaction_id' => $dto->originalTransactionId,
                'reversal_reason' => $dto->reason,
            ],
        );

        $result = $this->posting->execute($instruction);

        if ($result->success) {
            event(new TransactionReversed(
                originalTransactionId: $dto->originalTransactionId,
                reversalTransactionId: $result->transactionId,
                reason: $dto->reason,
                initiatedBy: $dto->initiatedBy,
            ));
        }

        return $result;
    }

    public function canReverse(string $originalTransactionId): array
    {
        $entries = $this->entries->findByReference('transaction', $originalTransactionId);
        if ($entries->isEmpty()) {
            return ['can_reverse' => false, 'reason' => 'Transaction not found'];
        }

        $reversals = $this->entries->findByReference('reversal', $originalTransactionId);
        if ($reversals->isNotEmpty()) {
            return ['can_reverse' => false, 'reason' => 'Transaction already reversed'];
        }

        return ['can_reverse' => true, 'reason' => null];
    }
}
