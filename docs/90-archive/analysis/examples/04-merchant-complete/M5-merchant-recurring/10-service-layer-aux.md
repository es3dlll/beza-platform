# 10 - RecurringBillingService

```php
<?php
namespace App\Services\Merchant;
use App\Models\MerchantSubscription;
use App\Models\SubscriptionCharge;
use App\Notifications\UpcomingCharge;
use App\Notifications\ChargeCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringBillingService
{
    public function __construct(private readonly MerchantWalletService $walletService) {}

    public function sendUpcomingNotifications(): void {
        $dueSoon = MerchantSubscription::where('status', 'active')
            ->whereDate('next_charge_at', now()->addDays(3))
            ->get();
        foreach ($dueSoon as $sub) {
            try { $sub->customer->notify(new UpcomingCharge($sub)); }
            catch (\Throwable $e) { Log::warning('فشل إشعار الشحن القادم', ['sub_id' => $sub->id]); }
        }
    }

    public function processDueCharges(): int {
        $processed = 0;
        $dueSubs = MerchantSubscription::where('status', 'active')
            ->where('next_charge_at', '<=', now())
            ->whereColumn('current_cycle', '<', 'max_cycles')
            ->get();

        foreach ($dueSubs as $sub) {
            try {
                DB::transaction(function () use ($sub, &$processed) {
                    $wallet = $this->walletService->getWallet($sub->merchant_id, $sub->currency);
                    $this->walletService->increment($wallet, $sub->amount);
                    $sub->increment('current_cycle');
                    $nextCharge = $sub->interval === 'monthly' ? now()->addMonth() : now()->addYear();
                    $sub->update(['next_charge_at' => $nextCharge]);
                    if ($sub->isComplete()) $sub->update(['status' => 'completed']);
                    SubscriptionCharge::create([
                        'subscription_id' => $sub->id,
                        'cycle_number'    => $sub->current_cycle,
                        'amount'          => $sub->amount,
                        'status'          => 'completed',
                        'charged_at'      => now(),
                    ]);
                    $sub->customer->notify(new ChargeCompleted($sub));
                    $processed++;
                });
            } catch (\Throwable $e) {
                Log::error('فشل معالجة شحن متكرر', ['sub_id' => $sub->id, 'error' => $e->getMessage()]);
            }
        }
        return $processed;
    }
}
```
