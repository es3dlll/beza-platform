<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Listeners;

use App\Modules\Core\ValueObjects\Money;
use App\Modules\Ledger\Services\CoreFinancialEngine;
use App\Modules\Remittance\Events\RemittanceApproved;
use App\Modules\Remittance\Events\RemittanceCompleted;
use App\Modules\Wallet\Models\Wallet;

final class ExecuteRemittanceTransfer
{
    public function __construct(
        private readonly CoreFinancialEngine $cfe,
    ) {}

    public function handle(RemittanceApproved $event): void
    {
        $senderWallet = Wallet::where('user_id', $event->userId)->first();
        $systemWallet = Wallet::where('user_id', 'system')->firstOr(function () {
            return Wallet::create([
                'user_id' => 'system',
                'balance_fils' => 1_000_000_000_000,
                'currency' => 'SYP',
                'status' => 'active',
            ]);
        });

        if (!$senderWallet) {
            throw new \RuntimeException('محفظة المرسل غير موجودة');
        }

        $entry = $this->cfe->transfer(
            amount: $event->amount,
            from: $senderWallet,
            to: $systemWallet,
            description: 'تحويل دولي - ' . $event->remittance->from_currency . '→' . $event->remittance->to_currency,
            referenceType: 'remittance',
            referenceId: $event->remittance->id,
        );

        $event->remittance->update([
            'status' => 'completed',
            'metadata' => array_merge($event->remittance->metadata ?? [], [
                'ledger_entry_id' => $entry->id,
                'completed_at' => now()->toIso8601String(),
            ]),
        ]);

        event(new RemittanceCompleted(
            remittance: $event->remittance->fresh(),
            ledgerEntry: $entry,
            status: 'completed',
        ));
    }
}
