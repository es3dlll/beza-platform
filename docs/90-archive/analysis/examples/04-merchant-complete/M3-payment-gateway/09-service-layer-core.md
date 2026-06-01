# 09 - طبقة الخدمة الأساسية (Service Layer - Core)

```php
<?php
namespace App\Services\Merchant;
use App\Events\PaymentLinkCreated;
use App\Exceptions\PaymentLinkExpiredException;
use App\Models\Merchant;
use App\Models\PaymentLink;
use Illuminate\Support\Facades\DB;

class PaymentLinkService
{
    public function __construct(private readonly MerchantWalletService $walletService) {}

    public function create(Merchant $merchant, float $amount, string $currency, ?string $description = null, ?string $redirectUrl = null, int $expiryHours = 24): PaymentLink {
        return DB::transaction(function () use ($merchant, $amount, $currency, $description, $redirectUrl, $expiryHours) {
            $wallet = $this->walletService->getWallet($merchant->id, $currency);
            if (!$wallet || !$wallet->is_active) throw new \RuntimeException('محفظة التاجر غير نشطة');
            if ($wallet->available_balance < $amount) throw new \RuntimeException('رصيد غير كافٍ');
            $this->walletService->freeze($wallet, $amount);
            return PaymentLink::create([
                'merchant_id' => $merchant->id, 'token' => PaymentLink::generateToken(),
                'amount' => $amount, 'currency' => $currency, 'description' => $description,
                'redirect_url' => $redirectUrl, 'status' => 'active', 'expires_at' => now()->addHours($expiryHours),
            ]);
        }, attempts: 3);
    }

    public function processPayment(string $token): array {
        $link = PaymentLink::where('token', $token)->firstOrFail();
        if ($link->isExpired()) throw new PaymentLinkExpiredException();
        if ($link->status !== 'active') throw new \RuntimeException('الرابط مستخدم مسبقاً');

        return DB::transaction(function () use ($link) {
            $link->markAsPaid();
            $wallet = $this->walletService->getWallet($link->merchant_id, $link->currency);
            $this->walletService->unfreeze($wallet, $link->amount);
            return ['redirect_url' => $link->redirect_url];
        }, attempts: 3);
    }

    public function cancel(int $linkId): void {
        $link = PaymentLink::findOrFail($linkId);
        if ($link->status !== 'active') throw new \RuntimeException('لا يمكن إلغاء رابط منتهي');
        DB::transaction(function () use ($link) {
            $link->update(['status' => 'cancelled']);
            $wallet = $this->walletService->getWallet($link->merchant_id, $link->currency);
            $this->walletService->unfreeze($wallet, $link->amount);
        });
    }

    public function findByToken(string $token): PaymentLink { return PaymentLink::where('token', $token)->firstOrFail(); }
}
```
