# 08 - كود المتحكم الكامل (Controller Full Code)

## CardController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Card\CardManagementRequest;
use App\Models\Card;
use App\Models\Wallet;
use App\Services\Card\CardManagementService;
use Illuminate\Http\JsonResponse;

class CardController extends Controller
{
    public function __construct(
        private readonly CardManagementService $cardService
    ) {}

    public function index(): JsonResponse
    {
        $cards = Card::where('user_id', auth()->id())
            ->select(['id', 'pan_masked', 'card_type', 'currency', 'status', 'daily_limit', 'monthly_limit', 'expiry_date', 'created_at'])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $cards]);
    }

    public function show(Card $card): JsonResponse
    {
        $this->authorize('view', $card);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $card->id,
                'pan_masked' => $card->pan_masked,
                'card_type' => $card->card_type,
                'currency' => $card->currency,
                'status' => $card->status,
                'daily_limit' => $card->daily_limit,
                'daily_used' => $card->daily_used,
                'monthly_limit' => $card->monthly_limit,
                'monthly_used' => $card->monthly_used,
                'expiry_date' => $card->expiry_date,
                'issued_at' => $card->issued_at,
                'frozen_at' => $card->frozen_at,
            ],
        ]);
    }

    public function update(CardManagementRequest $request, Card $card): JsonResponse
    {
        $this->authorize('update', $card);

        $validated = $request->validated();
        $action = $validated['action'];

        $result = match ($action) {
            'change_status' => $this->cardService->toggleFreeze($card),
            'change_pin' => $this->cardService->changePin($card, $validated['new_pin']),
            'update_limit' => $this->cardService->updateLimits($card, $validated),
            default => throw new \InvalidArgumentException('Invalid action'),
        };

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function destroy(Card $card): JsonResponse
    {
        $this->authorize('update', $card);

        $wallet = Wallet::where('user_id', $card->user_id)
            ->where('currency', $card->currency)
            ->firstOrFail();

        $this->cardService->closeCard($card, $wallet);

        return response()->json(['success' => true, 'message' => 'Card closed successfully']);
    }
}
```
