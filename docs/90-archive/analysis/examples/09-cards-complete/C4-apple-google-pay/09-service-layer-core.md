# 09 - طبقة الخدمة (Service Layer)

## Service Class

```php
<?php

namespace App\Services;

use App\Events\CardProvisioned;
use App\Events\WalletPaymentProcessed;
use App\Models\Card;
use App\Models\WalletToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletProvisioningService
{
    public function __construct(
        private readonly ApplePayService $applePayService,
        private readonly GooglePayService $googlePayService,
    ) {}

    public function provisionCard(Card $card, string $deviceId, string $walletType): array
    {
        return DB::transaction(function () use ($card, $deviceId, $walletType) {
            $deviceToken = Str::random(64);
            $panEncrypted = Crypt::encryptString($card->card_number);

            $walletService = match ($walletType) {
                'apple_pay' => $this->applePayService,
                'google_pay' => $this->googlePayService,
            };

            $passData = $walletService->createPass($card, $deviceToken);

            $card->walletTokens()->create([
                'device_id' => $deviceId,
                'device_type' => $walletType,
                'token' => hash('sha256', $deviceToken),
                'status' => 'active',
            ]);

            event(new CardProvisioned($card, $deviceId, $walletType));

            return [
                'device_token' => $deviceToken,
                'pass_data' => $passData,
            ];
        }, attempts: 3);
    }

    public function processWalletPayment(array $paymentData): array
    {
        return DB::transaction(function () use ($paymentData) {
            $decryptedData = Crypt::decryptString($paymentData['cryptogram']);

            event(new WalletPaymentProcessed($paymentData));

            return [
                'success' => true,
                'transaction_id' => Str::uuid()->toString(),
                'auth_code' => strtoupper(Str::random(6)),
            ];
        }, attempts: 3);
    }

    public function removeCard(string $deviceToken): void
    {
        DB::transaction(function () use ($deviceToken) {
            $tokenHash = hash('sha256', $deviceToken);
            $walletToken = WalletToken::where('token', $tokenHash)->firstOrFail();
            $walletToken->update(['status' => 'inactive']);
        });
    }
}
```

## Events Dispatched

| Event | Payload |
|-------|---------|
| CardProvisioned | Card, deviceId, walletType |
| WalletPaymentProcessed | paymentData array |
