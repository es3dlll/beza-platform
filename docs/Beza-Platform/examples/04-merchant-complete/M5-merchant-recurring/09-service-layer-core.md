# 09 - SubscriptionService كامل

```php
<?php
namespace App\Services\Merchant;
use App\Events\SubscriptionCreated;
use App\Models\Merchant;
use App\Models\MerchantSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function create(Merchant $merchant, string $customerPhone, float $amount, string $currency, string $interval, ?string $description = null, int $maxCycles = 12): MerchantSubscription
    {
        $customer = User::where('phone', $customerPhone)->where('status', 'active')->firstOrFail();

        $sub = DB::transaction(function () use ($merchant, $customer, $amount, $currency, $interval, $description, $maxCycles) {
            return MerchantSubscription::create([
                'merchant_id'    => $merchant->id,
                'customer_id'    => $customer->id,
                'amount'         => $amount,
                'currency'       => $currency,
                'interval'       => $interval,
                'status'         => 'pending',
                'max_cycles'     => $maxCycles,
                'current_cycle'  => 0,
                'next_charge_at' => null,
            ]);
        });

        SubscriptionCreated::dispatch($sub);
        return $sub;
    }

    public function confirmConsent(int $subscriptionId): void {
        $sub = MerchantSubscription::findOrFail($subscriptionId);
        if ($sub->status !== 'pending') throw new \RuntimeException('الاشتراك ليس في حالة انتظار');

        $nextCharge = $sub->interval === 'monthly' ? now()->addMonth() : now()->addYear();
        $sub->update([
            'status' => 'active',
            'customer_consented_at' => now(),
            'next_charge_at' => $nextCharge,
        ]);
    }

    public function cancel(int $subscriptionId): void {
        $sub = MerchantSubscription::findOrFail($subscriptionId);
        $sub->update(['status' => 'cancelled', 'next_charge_at' => null]);
    }

    public function cancel(int $merchantId, int $subId): void {
        $sub = MerchantSubscription::where('merchant_id', $merchantId)->findOrFail($subId);
        $sub->update(['status' => 'cancelled']);
    }
}
```
