# 10 - طبقة الخدمة المساعدة (Service Layer - Auxiliary)

```php
<?php
namespace App\Services\Merchant;
use App\Models\Merchant;
use App\Models\MerchantWallet;
use Illuminate\Support\Facades\DB;

class MerchantWalletService
{
    public function createWallets(Merchant $merchant): void
    {
        foreach (['SYP', 'USD'] as $currency) {
            MerchantWallet::create([
                'merchant_id' => $merchant->id, 'currency' => $currency,
                'wallet_number' => $this->generateWalletNumber($currency),
                'balance' => 0.00, 'frozen_balance' => 0.00, 'is_active' => true,
            ]);
        }
    }

    public function decrement(MerchantWallet $wallet, float $amount): void
    {
        $affected = DB::update(
            'UPDATE merchant_wallets SET balance = balance - ? WHERE id = ? AND balance >= ? AND is_active = ?',
            [$amount, $wallet->id, $amount, true]
        );
        if ($affected === 0) throw new \RuntimeException('فشل خصم رصيد التاجر');
    }

    public function increment(MerchantWallet $wallet, float $amount): void
    {
        DB::update('UPDATE merchant_wallets SET balance = balance + ? WHERE id = ? AND is_active = ?',
            [$amount, $wallet->id, true]);
    }

    public function getWallet(int $merchantId, string $currency): ?MerchantWallet
    {
        return MerchantWallet::where('merchant_id', $merchantId)
            ->where('currency', $currency)->first();
    }

    private function generateWalletNumber(string $currency): string
    {
        $prefix = $currency === 'SYP' ? '72' : '73';
        do { $number = $prefix . str_pad(random_int(0, 9999999999), 10, '0', STR_PAD_LEFT); }
        while (MerchantWallet::where('wallet_number', $number)->exists());
        return $number;
    }
}
```
