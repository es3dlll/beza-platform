# 08 - كود المتحكم الكامل (Controller Full Code)

## Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProvisionRequest;
use App\Models\Card;
use App\Services\WalletProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletProvisioningService $walletProvisioningService
    ) {}

    public function provision(Card $card, ProvisionRequest $request): JsonResponse
    {
        $result = $this->walletProvisioningService->provisionCard(
            $card,
            $request->input('device_id'),
            $request->input('wallet_type')
        );

        return response()->json(['data' => $result], 201);
    }

    public function transact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dpan' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:100'],
            'currency' => ['string', 'default:SYP'],
            'merchant_id' => ['required', 'integer'],
            'cryptogram' => ['required', 'string'],
        ]);

        $result = $this->walletProvisioningService->processWalletPayment($validated);

        return response()->json($result);
    }

    public function remove(string $token): JsonResponse
    {
        $this->walletProvisioningService->removeCard($token);

        return response()->json(['message' => 'تم إزالة البطاقة من المحفظة الرقمية'], 200);
    }
}
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/cards/wallet/provision | Provision card to Apple Pay / Google Pay |
| POST | /api/cards/wallet/transact | Process wallet payment |
| DELETE | /api/cards/wallet/{token} | Remove card from wallet |
