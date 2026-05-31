# 10 - RewardService كامل

```php
<?php
// app/Services/RewardService.php

namespace App\Services;

use App\Models\ReferralReward;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ReferralRewardReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * صرف المكافأة — تُستدعى بعد أول معاملة للمدعو ≥ 10 USD
     */
    public function payReward(User $referredUser): void
    {
        $pendingRewards = ReferralReward::where('referred_id', $referredUser->id)
            ->where('status', 'pending')
            ->get();

        foreach ($pendingRewards as $reward) {
            DB::transaction(function () use ($reward, $referredUser) {
                // ─── مكافأة الداعي ───
                $referrer = User::find($reward->referrer_id);
                if ($referrer) {
                    $referrerWallet = $this->walletService->getWallet($referrer->id, 'USD');
                    if ($referrerWallet) {
                        $this->walletService->increment($referrerWallet, $reward->referrer_amount);

                        Transaction::create([
                            'to_wallet_id'    => $referrerWallet->id,
                            'amount'          => $reward->referrer_amount,
                            'amount_in_usd'   => $reward->referrer_amount,
                            'type'            => 'referral_bonus',
                            'status'          => 'completed',
                            'reference_number'=> Transaction::generateReferenceNumber(),
                            'description'     => 'مكافأة دعوة صديق',
                            'completed_at'    => now(),
                        ]);

                        $referrer->notify(new ReferralRewardReceived(
                            amount: $reward->referrer_amount,
                            type: 'referrer',
                        ));
                    }
                }

                // ─── مكافأة المدعو ───
                $referredWallet = $this->walletService->getWallet($referredUser->id, 'USD');
                if ($referredWallet) {
                    $this->walletService->increment($referredWallet, $reward->referred_amount);

                    Transaction::create([
                        'to_wallet_id'    => $referredWallet->id,
                        'amount'          => $reward->referred_amount,
                        'amount_in_usd'   => $reward->referred_amount,
                        'type'            => 'referral_bonus',
                        'status'          => 'completed',
                        'reference_number'=> Transaction::generateReferenceNumber(),
                        'description'     => 'مكافأة تسجيل عبر دعوة',
                        'completed_at'    => now(),
                    ]);

                    $referredUser->notify(new ReferralRewardReceived(
                        amount: $reward->referred_amount,
                        type: 'referred',
                    ));
                }

                // ─── تحديث حالة المكافأة ───
                $reward->update(['status' => 'paid']);
            }, attempts: 3);
        }
    }
}
```
