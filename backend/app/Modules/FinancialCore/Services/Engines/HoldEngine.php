<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Services\Engines;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use App\Modules\FinancialCore\Events\TransactionHeld;
use App\Modules\FinancialCore\Events\TransactionReversed;
use App\Modules\FinancialCore\Models\Transaction;
use App\Modules\FinancialCore\Services\IdempotencyService;
use App\Modules\FinancialCore\Exceptions\HoldNotFoundException;
use App\Modules\FinancialCore\Exceptions\InvalidStateTransitionException;
use App\Modules\Fraud\Services\FraudGuard;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Services\JournalService;
use Illuminate\Support\Facades\DB;

final class HoldEngine
{
    public function __construct(
        private readonly JournalService $journalService,
        private readonly IdempotencyService $idempotencyService,
        private readonly ?FraudGuard $fraudGuard = null,
    ) {}

    public function placeHold(
        string $walletId,
        Money $amount,
        string $description,
        string $descriptionAr,
        string $currency = 'SYP',
        ?string $idempotencyKey = null,
    ): array {
        if ($this->fraudGuard !== null) {
            $this->fraudGuard->preCheck(
                walletId: $walletId,
                amount: $amount->amount(),
            );
        }

        if ($idempotencyKey !== null) {
            $cached = $this->idempotencyService->checkOrCreate($idempotencyKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $customerAccount = LedgerAccount::where('code', '1100')->firstOrFail();
        $liquidityAccount = LedgerAccount::where('code', '1400')->firstOrFail();

        $transaction = DB::transaction(function () use ($walletId, $amount, $currency, $description, $descriptionAr, $idempotencyKey, $customerAccount, $liquidityAccount) {
            $tx = Transaction::create([
                'type' => 'hold',
                'status' => 'initiated',
                'wallet_id' => $walletId,
                'from_account_id' => $customerAccount->id,
                'to_account_id' => $liquidityAccount->id,
                'amount' => $amount->amount(),
                'currency' => $currency,
                'description' => $description,
                'description_ar' => $descriptionAr,
                'idempotency_key' => $idempotencyKey,
            ]);

            $journal = $this->journalService->postEntry(
                $tx->id,
                [['account_id' => $customerAccount->id, 'amount' => $amount->amount()]],
                [['account_id' => $liquidityAccount->id, 'amount' => $amount->amount()]],
                "Hold for transaction {$tx->id}",
                "حجز للمعاملة {$tx->id}",
            );

            $tx->update(['status' => 'held', 'journal_entry_id' => $journal->id]);

            if ($idempotencyKey !== null) {
                $this->idempotencyService->complete($idempotencyKey, $tx->id, $tx->toArray());
            }

            return ['transaction' => $tx, 'journal' => $journal];
        });

        if ($this->fraudGuard !== null) {
            $this->fraudGuard->postMonitor(
                walletId: $walletId,
                transactionId: $transaction['transaction']->id,
                amount: $amount->amount(),
            );
        }

        event(new TransactionHeld(
            transactionId: $transaction['transaction']->id,
            amount: $amount->amount(),
            walletId: $walletId,
            journalEntryId: $transaction['journal']->id,
        ));

        return $transaction;
    }

    public function releaseHold(string $transactionId): array
    {
        $tx = Transaction::findOrFail($transactionId);

        if ($tx->status !== 'held') {
            throw new InvalidStateTransitionException("Cannot release hold in status: {$tx->status}");
        }

        $reversal = DB::transaction(function () use ($tx) {
            $originalEntry = $tx->journalEntry;
            if ($originalEntry === null) {
                throw new HoldNotFoundException("No journal entry for hold {$tx->id}");
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

            $journal = $this->journalService->postEntry(
                $tx->id,
                $reversalDebits,
                $reversalCredits,
                "Release hold for transaction {$tx->id}",
                "إلغاء حجز المعاملة {$tx->id}",
            );

            $tx->update(['status' => 'reversed', 'journal_entry_id' => $journal->id]);

            return ['transaction' => $tx, 'journal' => $journal];
        });

        event(new TransactionReversed(
            originalTransactionId: $transactionId,
            reversalTransactionId: $reversal['transaction']->id,
            reason: 'Hold released',
            journalEntryId: $reversal['journal']->id,
        ));

        return $reversal;
    }

    public function captureHold(string $transactionId): Transaction
    {
        $tx = Transaction::findOrFail($transactionId);

        if ($tx->status !== 'held') {
            throw new InvalidStateTransitionException("Cannot capture hold in status: {$tx->status}");
        }

        $tx->update(['status' => 'posted']);
        return $tx;
    }
}
