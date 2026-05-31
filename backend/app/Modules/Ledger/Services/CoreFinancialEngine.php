<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Modules\Core\ValueObjects\Money;
use App\Modules\Ledger\Events\TransferCompleted;
use App\Modules\Ledger\Models\LedgerEntry;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Support\Facades\DB;

final class CoreFinancialEngine
{
    public function transfer(Money $amount, Wallet $from, Wallet $to, ?string $description = null, ?string $referenceType = null, ?string $referenceId = null, ?string $fromUserId = null, ?string $toUserId = null): LedgerEntry
    {
        $this->assertSameCurrency($from, $to);

        if ($from->balance_fils < $amount->fils()) {
            throw new \RuntimeException('الرصيد غير كافٍ');
        }

        $fromBefore = (int) $from->balance_fils;
        $toBefore = (int) $to->balance_fils;

        $entry = DB::transaction(function () use ($amount, $from, $to, $description, $referenceType, $referenceId, $fromBefore, $toBefore) {
            $from->decrement('balance_fils', $amount->fils());
            $to->increment('balance_fils', $amount->fils());

            $fromAfter = (int) $from->balance_fils;
            $toAfter = (int) $to->balance_fils;

            return LedgerEntry::create([
                'debit_wallet_id' => $from->id,
                'credit_wallet_id' => $to->id,
                'amount_fils' => $amount->fils(),
                'currency' => $amount->currency()->value,
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'metadata' => [
                    'from_balance_before' => $fromBefore,
                    'from_balance_after' => $fromAfter,
                    'to_balance_before' => $toBefore,
                    'to_balance_after' => $toAfter,
                ],
            ]);
        });

        event(new TransferCompleted(
            entry: $entry,
            fromUserId: $fromUserId ?? $from->user_id,
            toUserId: $toUserId ?? $to->user_id,
            amount: $amount,
            requestId: $referenceId,
        ));

        return $entry;
    }

    private function assertSameCurrency(Wallet $a, Wallet $b): void
    {
        if ($a->currency !== $b->currency) {
            throw new \RuntimeException('عملة المحفظتين غير متطابقة');
        }
    }
}
