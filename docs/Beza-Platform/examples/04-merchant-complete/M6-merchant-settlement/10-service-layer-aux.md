# 10 - SettlementAdminService

```php
<?php
namespace App\Services\Merchant;
use App\Models\MerchantSettlement;
use App\Notifications\SettlementCompletedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlementAdminService
{
    public function approve(int $settlementId, string $bankRef): void
    {
        $settlement = MerchantSettlement::findOrFail($settlementId);
        if ($settlement->status !== 'pending') throw new \RuntimeException('التسوية ليست في حالة انتظار');

        DB::transaction(function () use ($settlement, $bankRef) {
            $settlement->update([
                'status' => 'completed',
                'bank_transaction_ref' => $bankRef,
                'settlement_date' => now(),
            ]);
        });

        try {
            $settlement->merchant->user->notify(new SettlementCompletedNotification($settlement));
        } catch (\Throwable $e) {
            Log::warning('فشل إشعار إتمام التسوية', ['settlement_id' => $settlement->id]);
        }
    }

    public function reject(int $settlementId, string $reason): void
    {
        $settlement = MerchantSettlement::findOrFail($settlementId);
        DB::transaction(function () use ($settlement, $reason) {
            $settlement->update(['status' => 'failed', 'metadata->reject_reason' => $reason]);
            // إعادة الرصيد للمحفظة
            $wallet = MerchantWallet::where('merchant_id', $settlement->merchant_id)
                ->where('currency', $settlement->currency)->first();
            if ($wallet) $wallet->increment('balance', $settlement->net_amount);
        });
    }
}
```
