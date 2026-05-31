# 10 - DisputeResolutionService

```php
<?php
// app/Services/DisputeResolutionService.php

namespace App\Services;

use App\Events\Admin\DisputeResolved;
use App\Exceptions\Admin\DisputeAlreadyResolvedException;
use App\Exceptions\Admin\DisputeExpiredException;
use App\Models\Dispute;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisputeResolutionService
{
    public function resolve(
        int     $disputeId,
        int     $adminId,
        string  $resolution,
        ?float  $partialAmount = null,
        ?string $notes = null,
    ): Dispute {
        $dispute = Dispute::with('transaction')->findOrFail($disputeId);

        $this->validateResolution($dispute);

        DB::transaction(function () use ($dispute, $adminId, $resolution, $partialAmount, $notes) {
            $dispute->update([
                'status'        => $resolution === 'reject' ? 'rejected' : 'resolved',
                'resolution'    => $resolution,
                'partial_amount'=> $partialAmount,
                'admin_notes'   => $notes,
                'resolved_by'   => $adminId,
                'resolved_at'   => now(),
            ]);

            if ($resolution === 'refund' || $resolution === 'partial_refund') {
                $this->processRefund($dispute, $partialAmount);
            }

            if ($resolution === 'reject') {
                Log::info("Dispute rejected", [
                    'dispute_id' => $dispute->id,
                    'by_admin'   => $adminId,
                ]);
            }
        });

        DisputeResolved::dispatch($dispute);

        return $dispute->fresh();
    }

    private function validateResolution(Dispute $dispute): void
    {
        if (!in_array($dispute->status, ['open', 'investigating'])) {
            throw new DisputeAlreadyResolvedException();
        }

        if ($dispute->isExpired()) {
            $dispute->update([
                'status'         => 'resolved',
                'resolution'     => 'reject',
                'auto_closed_at' => now(),
            ]);
            throw new DisputeExpiredException();
        }
    }

    private function processRefund(Dispute $dispute, ?float $partialAmount): void
    {
        $transaction = $dispute->transaction;
        $amount = $partialAmount ?? $transaction->amount;

        // خصم من محفظة التاجر (المرسل الأصلي أو respondent)
        $respondentWallet = Wallet::where('user_id', $dispute->respondent_id)
            ->where('currency', $transaction->fromWallet->currency)
            ->first();

        if (!$respondentWallet || $respondentWallet->available_balance < $amount) {
            throw new \RuntimeException('رصيد التاجر غير كافٍ للاسترجاع');
        }

        // إضافة للمشتري (complainant)
        $complainantWallet = Wallet::where('user_id', $dispute->complainant_id)
            ->where('currency', $transaction->fromWallet->currency)
            ->first();

        // تنفيذ الاسترجاع
        $respondentWallet->decrement('balance', $amount);
        $complainantWallet->increment('balance', $amount);

        // تحديث حالة المعاملة
        $transaction->update(['status' => 'refunded']);

        Log::info("Refund processed", [
            'dispute_id'   => $dispute->id,
            'amount'       => $amount,
            'from_user'    => $dispute->respondent_id,
            'to_user'      => $dispute->complainant_id,
        ]);
    }
}
```
