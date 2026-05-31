<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Services\Engines;

use App\Domain\Enums\Currency;
use App\Domain\ValueObjects\Money;
use App\Modules\FinancialCore\Events\TransactionPosted;
use App\Modules\FinancialCore\Models\Transaction;
use App\Modules\FinancialCore\Services\IdempotencyService;
use App\Modules\Fraud\Services\FraudGuard;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Services\JournalService;
use Illuminate\Support\Facades\DB;

final class PostingEngine
{
    public function __construct(
        private readonly JournalService $journalService,
        private readonly IdempotencyService $idempotencyService,
        private readonly ?FraudGuard $fraudGuard = null,
    ) {}

    public function execute(
        string $fromWalletId,
        string $toWalletId,
        Money $amount,
        string $description,
        string $descriptionAr,
        string $currency = 'SYP',
        ?string $idempotencyKey = null,
        ?callable $feeCallback = null,
    ): array {
        if ($this->fraudGuard !== null) {
            $this->fraudGuard->preCheck(
                walletId: $fromWalletId,
                amount: $amount->amount(),
            );
        }

        if ($idempotencyKey !== null) {
            $cached = $this->idempotencyService->checkOrCreate($idempotencyKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $fromAccount = LedgerAccount::where('code', '1100')->firstOrFail();
        $toAccount = LedgerAccount::where('code', '1100')->firstOrFail();

        $result = DB::transaction(function () use ($fromWalletId, $toWalletId, $amount, $currency, $description, $descriptionAr, $idempotencyKey, $fromAccount, $toAccount, $feeCallback) {
            $tx = Transaction::create([
                'type' => 'post',
                'status' => 'initiated',
                'wallet_id' => $fromWalletId,
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'amount' => $amount->amount(),
                'currency' => $currency,
                'description' => $description,
                'description_ar' => $descriptionAr,
                'idempotency_key' => $idempotencyKey,
            ]);

            $journal = $this->journalService->postEntry(
                $tx->id,
                [['account_id' => $fromAccount->id, 'amount' => $amount->amount()]],
                [['account_id' => $toAccount->id, 'amount' => $amount->amount()]],
                "Transfer {$tx->id}",
                "تحويل {$tx->id}",
            );

            $tx->update(['status' => 'posted', 'journal_entry_id' => $journal->id]);

            if ($feeCallback !== null) {
                $feeCallback($tx, $amount);
            }

            if ($idempotencyKey !== null) {
                $this->idempotencyService->complete($idempotencyKey, $tx->id, $tx->toArray());
            }

            return ['transaction' => $tx, 'journal' => $journal];
        });

        if ($this->fraudGuard !== null) {
            $this->fraudGuard->postMonitor(
                walletId: $fromWalletId,
                transactionId: $result['transaction']->id,
                amount: $amount->amount(),
            );
        }

        event(new TransactionPosted(
            transactionId: $result['transaction']->id,
            amount: $amount->amount(),
            fromWalletId: $fromWalletId,
            toWalletId: $toWalletId,
            journalEntryId: $result['journal']->id,
        ));

        return $result;
    }
}
