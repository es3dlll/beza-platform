<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Services\Engines;

use App\Domain\ValueObjects\Money;
use App\Modules\FinancialCore\Events\FeeApplied;
use App\Modules\FinancialCore\Models\FeeRule;
use App\Modules\FinancialCore\Models\Transaction;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Services\JournalService;
use Illuminate\Support\Facades\DB;

final class FeeEngine
{
    public function __construct(
        private readonly JournalService $journalService,
    ) {}

    public function applyFee(
        Money $amount,
        string $feeRuleId,
        string $transactionId,
        string $description,
        string $descriptionAr,
    ): array {
        $rule = FeeRule::findOrFail($feeRuleId);
        $feeMoney = $rule->calculateFee($amount);

        $feeAccount = LedgerAccount::where('code', $rule->account_code)->firstOrFail();
        $originalTx = Transaction::findOrFail($transactionId);
        $senderAccount = LedgerAccount::findOrFail($originalTx->from_account_id);

        $result = DB::transaction(function () use ($feeMoney, $transactionId, $description, $descriptionAr, $rule, $feeAccount, $senderAccount) {
            $feeTx = Transaction::create([
                'type' => 'fee',
                'status' => 'posted',
                'wallet_id' => $originalTx->wallet_id ?? $senderAccount->id,
                'from_account_id' => $senderAccount->id,
                'to_account_id' => $feeAccount->id,
                'amount' => $feeMoney->amount(),
                'currency' => $feeMoney->currency()->value,
                'fee_amount' => $feeMoney->amount(),
                'fee_account_id' => $feeAccount->id,
                'fee_basis_points' => (int) $rule->value,
                'description' => $description,
                'description_ar' => $descriptionAr,
            ]);

            $journal = $this->journalService->postEntry(
                $feeTx->id,
                [['account_id' => $senderAccount->id, 'amount' => $feeMoney->amount()]],
                [['account_id' => $feeAccount->id, 'amount' => $feeMoney->amount()]],
                "Fee for transaction {$transactionId}",
                "رسوم المعاملة {$transactionId}",
            );

            $feeTx->update(['journal_entry_id' => $journal->id]);

            return ['transaction' => $feeTx, 'journal' => $journal, 'feeMoney' => $feeMoney];
        });

        event(new FeeApplied(
            transactionId: $transactionId,
            feeTransactionId: $result['transaction']->id,
            feeAmount: $feeMoney->amount(),
            feeAccountId: $feeAccount->id,
        ));

        return $result;
    }
}
