<?php

declare(strict_types=1);

namespace App\Modules\Bills\Services;

use App\Modules\Bills\Events\BillPaymentApproved;
use App\Modules\Bills\Events\BillPaymentCompleted;
use App\Modules\Bills\Events\BillPaymentFailed;
use App\Modules\Bills\Events\BillPaymentInitiated;
use App\Modules\Bills\Models\Bill;
use App\Modules\Bills\Models\ScheduledPayment;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\Fraud\Jobs\FraudDetectionEngine;
use App\Models\User;
use App\Modules\Ledger\Services\CoreFinancialEngine;
use App\Modules\Wallet\Models\Wallet;

final class BillPaymentProcessor
{
    public function __construct(
        private readonly CoreFinancialEngine $cfe,
    ) {}

    public function payBill(Bill $bill, User $user): Bill
    {
        if (!$bill->canBePaid()) {
            throw new \RuntimeException('الفاتورة غير قابلة للدفع - الحالة: ' . $bill->status);
        }

        $amount = Money::fromFils($bill->amount_fils);
        $this->validateAndExecute($bill, $amount, $user);

        return $bill->fresh();
    }

    public function processScheduledPayment(ScheduledPayment $schedule, User $user): ?Bill
    {
        $amount = Money::fromFils($schedule->amount_fils);
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet || $wallet->balance_fils < $amount->fils()) {
            return null;
        }

        $bill = Bill::create([
            'user_id' => $user->id,
            'bill_provider_id' => $schedule->bill_provider_id,
            'account_number' => $schedule->account_number,
            'amount_fils' => $schedule->amount_fils,
            'due_date' => $schedule->next_execution_date,
            'status' => 'pending',
        ]);

        $this->validateAndExecute($bill, $amount, $user);

        $schedule->update([
            'last_executed_at' => now(),
            'next_execution_date' => $schedule->calculateNextDate(),
        ]);

        return $bill->fresh();
    }

    private function validateAndExecute(Bill $bill, Money $amount, User $user): void
    {
        $wallet = Wallet::where('user_id', $user->id)->first();
        if (!$wallet) {
            event(new BillPaymentFailed($bill, 'محفظة المستخدم غير موجودة'));
            throw new \RuntimeException('محفظة المستخدم غير موجودة');
        }

        if ($wallet->balance_fils < $amount->fils()) {
            $bill->update(['status' => 'failed', 'metadata' => array_merge($bill->metadata ?? [], ['failure_reason' => 'رصيد غير كاف'])]);
            event(new BillPaymentFailed($bill, 'رصيد غير كاف'));
            throw new \RuntimeException('رصيد غير كاف');
        }

        $systemUserId = config('bills.system_wallet_user_id', 'system');
        $systemWallet = Wallet::firstOrCreate(
            ['user_id' => $systemUserId],
            ['balance_fils' => 1_000_000_000_000, 'currency' => 'SYP'],
        );



        $event = new BillPaymentInitiated($bill, $amount, $user->id);
        event($event);

        $entry = $this->cfe->transfer(
            amount: $amount,
            from: $wallet,
            to: $systemWallet,
            description: 'دفع فاتورة - ' . $bill->account_number,
            referenceType: 'bill',
            referenceId: $bill->id,
        );

        $receipt = 'RCP-' . strtoupper(bin2hex(random_bytes(4)));
        $bill->update([
            'status' => 'paid',
            'paid_at' => now(),
            'receipt_reference' => $receipt,
            'metadata' => array_merge($bill->metadata ?? [], [
                'ledger_entry_id' => $entry->id,
                'completed_at' => now()->toIso8601String(),
            ]),
        ]);

        event(new BillPaymentCompleted($bill->fresh(), $entry, $receipt));
    }
}
