# 10 - طبقة الخدمة المساعدة (Service Layer - Auxiliary)

## PaymentLinkExpiryService
```php
<?php
namespace AppServicesMerchant;
use AppModelsPaymentLink;
use AppModelsMerchantWallet;
use IlluminateSupportFacadesDB;
use IlluminateSupportFacadesLog;

class PaymentLinkExpiryService
{
    public function expireOverdueLinks(): int {
        $count = PaymentLink::where('status', 'active')->where('expires_at', '<', now())->count();
        PaymentLink::where('status', 'active')->where('expires_at', '<', now())->chunkById(100, function ($links) {
            foreach ($links as $link) {
                DB::transaction(function () use ($link) {
                    $link->markAsExpired();
                    $wallet = MerchantWallet::where('merchant_id', $link->merchant_id)
                        ->where('currency', $link->currency)->first();
                    if ($wallet) {
                        $wallet->increment('balance', $link->amount);
                        $wallet->decrement('frozen_balance', $link->amount);
                    }
                    Log::info('Payment link expired, balance unfrozen', [
                        'link_id' => $link->id, 'merchant_id' => $link->merchant_id, 'amount' => $link->amount
                    ]);
                });
            }
        });
        return $count;
    }

    public function cleanupExpiredLinks(int $daysOld = 30): int {
        return PaymentLink::where('status', 'expired')
            ->where('expires_at', '<', now()->subDays($daysOld))->delete();
    }
}
```

## شرح الخدمة
- expireOverdueLinks: يجد الروابط منتهية الصلاحية ويفك تجميد الأرصدة
- cleanupExpiredLinks: ينظف الروابط القديمة بعد 30 يوماً
- تستخدم chunkById لمعالجة كميات كبيرة دون استهلاك ذاكرة
- تسجيل كل عملية في log للتتبع
