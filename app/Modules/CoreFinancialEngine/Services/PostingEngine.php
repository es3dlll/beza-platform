<?php

namespace Modules\CoreFinancialEngine\Services;

use Modules\CoreFinancialEngine\Contracts\PostingEngineInterface;
use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\DTOs\PostingResultDto;
use Modules\CoreFinancialEngine\Events\TransactionPosted;
use App\Modules\Ledger\DTOs\JournalLineDto;
use App\Modules\Ledger\DTOs\PostEntryDto;
use App\Modules\Ledger\Services\JournalService;
use Illuminate\Support\Str;

final class PostingEngine implements PostingEngineInterface
{
    public function __construct(
        private readonly JournalService $journal,
    ) {}

    public function execute(PostingInstructionDto $instruction): PostingResultDto
    {
        $errors = $this->validate($instruction);
        if (!empty($errors)) {
            return new PostingResultDto(
                success: false,
                transactionId: '',
                journalEntryId: '',
                totalAmount: 0,
                currency: 'SYP',
                errorCode: 'VALIDATION_ERROR',
                errorMessage: implode('; ', $errors),
            );
        }

        $lines = [];
        foreach ($instruction->lines as $line) {
            $lines[] = new JournalLineDto(
                accountId: $line['account_id'],
                amount: $line['amount'],
                type: $line['type'],
                description: $line['description'] ?? null,
            );
        }

        $postDto = new PostEntryDto(
            referenceType: $instruction->referenceType,
            referenceId: $instruction->referenceId,
            description: $instruction->description,
            lines: $lines,
            postedAt: null,
        );

        try {
            $entry = $this->journal->post($postDto);

            event(new TransactionPosted(
                transactionId: $instruction->referenceId,
                referenceType: $instruction->referenceType,
                referenceId: $instruction->referenceId,
                totalAmount: $entry->total_amount,
                currency: 'SYP',
                channel: $instruction->channel,
            ));

            return new PostingResultDto(
                success: true,
                transactionId: $instruction->referenceId,
                journalEntryId: $entry->id,
                totalAmount: $entry->total_amount,
                currency: 'SYP',
            );
        } catch (\Exception $e) {
            return new PostingResultDto(
                success: false,
                transactionId: $instruction->referenceId,
                journalEntryId: '',
                totalAmount: 0,
                currency: 'SYP',
                errorCode: class_basename($e),
                errorMessage: $e->getMessage(),
            );
        }
    }

    public function validate(PostingInstructionDto $instruction): array
    {
        $errors = [];

        if (empty($instruction->referenceType)) {
            $errors[] = 'reference_type is required';
        }
        if (empty($instruction->referenceId)) {
            $errors[] = 'reference_id is required';
        }
        if (empty($instruction->lines)) {
            $errors[] = 'at least one journal line is required';
        }

        $debitTotal = 0;
        $creditTotal = 0;
        foreach ($instruction->lines as $i => $line) {
            if (empty($line['account_id'])) {
                $errors[] = "line $i: account_id is required";
            }
            if (!isset($line['amount']) || $line['amount'] <= 0) {
                $errors[] = "line $i: amount must be positive";
            }
            if (!in_array($line['type'], ['debit', 'credit'])) {
                $errors[] = "line $i: type must be debit or credit";
            }
            if ($line['type'] === 'debit') {
                $debitTotal += $line['amount'];
            } else {
                $creditTotal += $line['amount'];
            }
        }

        if ($debitTotal !== $creditTotal) {
            $errors[] = 'debits must equal credits';
        }

        return $errors;
    }
}
