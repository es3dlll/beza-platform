<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Services;

use Modules\CoreFinancialEngine\Contracts\PostingEngineInterface;
use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\DTOs\PostingResultDto;
use Modules\CoreFinancialEngine\Events\TransactionPosted;
use Modules\CoreFinancialEngine\Models\CfeTransaction;
use Modules\CoreFinancialEngine\Models\CfeTransactionLine;
use Modules\Ledger\DTOs\JournalLineDto;
use Modules\Ledger\DTOs\PostEntryDto;
use Modules\Ledger\Models\LedgerAccount;
use Modules\Ledger\Services\JournalService;
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

        $lines = $this->balanceLines($instruction->lines);

        $postDto = new PostEntryDto(
            referenceType: $instruction->referenceType,
            referenceId: $instruction->referenceId,
            description: $instruction->description,
            lines: $lines,
            postedAt: null,
        );

        try {
            $entry = $this->journal->post($postDto);

            $transaction = CfeTransaction::create([
                'id' => Str::ulid()->toBase32(),
                'reference_type' => $instruction->referenceType,
                'reference_id' => $instruction->referenceId,
                'description' => $instruction->description,
                'total_amount' => $entry->total_amount,
                'currency' => 'SYP',
                'channel' => $instruction->channel,
                'initiated_by' => $instruction->initiatedBy,
                'status' => 'completed',
                'journal_entry_id' => $entry->id,
                'completed_at' => now(),
                'metadata' => $instruction->metadata,
            ]);

            foreach ($instruction->lines as $line) {
                CfeTransactionLine::create([
                    'id' => Str::ulid()->toBase32(),
                    'cfe_transaction_id' => $transaction->id,
                    'account_id' => $line['account_id'],
                    'amount' => $line['amount'],
                    'type' => $line['type'],
                    'description' => $line['description'] ?? null,
                ]);
            }

            event(new TransactionPosted(
                transactionId: $transaction->id,
                referenceType: $instruction->referenceType,
                referenceId: $instruction->referenceId,
                totalAmount: $entry->total_amount,
                currency: 'SYP',
                channel: $instruction->channel,
            ));

            return new PostingResultDto(
                success: true,
                transactionId: $transaction->id,
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

    private function balanceLines(array $lines): array
    {
        $debitTotal = 0;
        $creditTotal = 0;
        foreach ($lines as $line) {
            if ($line['type'] === 'debit') {
                $debitTotal += $line['amount'];
            } else {
                $creditTotal += $line['amount'];
            }
        }

        if ($debitTotal === $creditTotal) {
            return array_map(fn($l) => new JournalLineDto(
                accountId: $l['account_id'],
                amount: $l['amount'],
                type: $l['type'],
                description: $l['description'] ?? null,
            ), $lines);
        }

        $diff = abs($debitTotal - $creditTotal);
        $missingType = $debitTotal < $creditTotal ? 'debit' : 'credit';
        $suspense = LedgerAccount::where('type', 'suspense')->first();
        if (!$suspense) {
            $suspense = LedgerAccount::create([
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'account_number' => '9999-SUSPENSE',
                'name' => 'Suspense Account',
                'type' => 'suspense',
                'currency' => 'SYP',
                'balance' => 0,
                'available_balance' => 0,
            ]);
        }

        $result = array_map(fn($l) => new JournalLineDto(
            accountId: $l['account_id'],
            amount: $l['amount'],
            type: $l['type'],
            description: $l['description'] ?? null,
        ), $lines);

        $result[] = new JournalLineDto(
            accountId: $suspense->id,
            amount: $diff,
            type: $missingType,
            description: 'Auto-balanced contra entry',
        );

        return $result;
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
        }

        return $errors;
    }
}
