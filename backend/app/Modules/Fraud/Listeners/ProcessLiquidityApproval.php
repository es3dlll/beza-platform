<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Listeners;

use App\Modules\Core\ValueObjects\Money;
use App\Modules\Fraud\Events\LiquidityApproved;
use App\Modules\Fraud\Events\LiquidityCompleted;
use App\Modules\Ledger\Services\CoreFinancialEngine;
use App\Modules\Wallet\Models\Wallet;

final class ProcessLiquidityApproval
{
    public function __construct(
        private readonly CoreFinancialEngine $cfe,
    ) {}

    public function handle(LiquidityApproved $event): void
    {
        $agentWallet = Wallet::where('user_id', $event->agent->user_id)->first();
        $systemWallet = Wallet::where('user_id', 'system')->firstOr(function () {
            return Wallet::create([
                'user_id' => 'system',
                'balance_fils' => 1_000_000_000_000,
                'currency' => 'SYP',
                'status' => 'active',
            ]);
        });

        if (!$agentWallet) {
            throw new \RuntimeException('محفظة الوكيل غير موجودة');
        }

        $entry = $this->cfe->transfer(
            amount: $event->amount,
            from: $systemWallet,
            to: $agentWallet,
            description: 'تمويل سيولة وكيل - موافقة بعد فحص المخاطر',
            referenceType: 'liquidity_approval',
            referenceId: $event->requestId,
        );

        event(new LiquidityCompleted(
            agentId: $event->agent->id,
            requestId: $event->requestId,
            amountFils: $event->amount->fils(),
            ledgerEntry: $entry,
            status: 'completed',
        ));
    }
}
