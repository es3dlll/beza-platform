<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Services\Engines;

use App\Modules\FinancialCore\Events\TransactionReversed;
use App\Modules\FinancialCore\Models\Transaction;
use App\Modules\FinancialCore\Exceptions\CannotReverseException;
use App\Modules\FinancialCore\Services\IdempotencyService;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Services\JournalService;
use Illuminate\Support\Facades\DB;

final class ReversalEngine
{
    public function __construct(
        private readonly JournalService $journalService,
        private readonly IdempotencyService $idempotencyService,
    ) {}

    public function reverse(
        string $originalTransactionId,
        string $reason,
        string $reasonAr,
        ?string $idempotencyKey = null,
    ): array {
        $originalTx = Transaction::findOrFail($originalTransactionId);

        if (!in_array($originalTx->status, ['posted', 'held'], true)) {
            throw new CannotReverseException(
                "Cannot reverse transaction in status: {$originalTx->status}"
            );
        }

        if ($idempotencyKey !== null) {
            $cached = $this->idempotencyService->checkOrCreate($idempotencyKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $result = DB::transaction(function () use ($originalTx, $reason, $reasonAr, $idempotencyKey) {
            $originalEntry = $originalTx->journalEntry;
            if ($originalEntry === null) {
                throw new CannotReverseException("No journal entry for transaction {$originalTx->id}");
            }

            $lines = $originalEntry->lines;
            $reversalDebits = [];
            $reversalCredits = [];

            foreach ($lines as $line) {
                $entry = ['account_id' => $line->account_id, 'amount' => $line->amount];
                if ($line->type === 'debit') {
                    $reversalCredits[] = $entry;
                } else {
                    $reversalDebits[] = $entry;
                }
            }

            $reversalTx = Transaction::create([
                'type' => 'reversal',
                'status' => 'posted',
                'wallet_id' => $originalTx->wallet_id,
                'from_account_id' => $originalTx->to_account_id,
                'to_account_id' => $originalTx->from_account_id,
                'amount' => $originalTx->amount,
                'currency' => $originalTx->currency,
                'description' => $reason,
                'description_ar' => $reasonAr,
                'idempotency_key' => $idempotencyKey,
                'reversal_of' => $originalTx->id,
            ]);

            $journal = $this->journalService->postEntry(
                $reversalTx->id,
                $reversalDebits,
                $reversalCredits,
                "Reversal of {$originalTx->id}: {$reason}",
                "إلغاء {$originalTx->id}: {$reasonAr}",
            );

            $reversalTx->update(['journal_entry_id' => $journal->id]);
            $originalTx->update(['status' => 'reversed', 'reversed_by' => $reversalTx->id]);

            if ($idempotencyKey !== null) {
                $this->idempotencyService->complete($idempotencyKey, $reversalTx->id, $reversalTx->toArray());
            }

            return ['reversal' => $reversalTx, 'journal' => $journal];
        });

        event(new TransactionReversed(
            originalTransactionId: $originalTransactionId,
            reversalTransactionId: $result['reversal']->id,
            reason: $reason,
            journalEntryId: $result['journal']->id,
        ));

        return $result;
    }
}
