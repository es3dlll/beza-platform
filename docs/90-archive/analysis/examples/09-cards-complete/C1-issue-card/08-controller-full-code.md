# 08 - كود المتحكم الكامل (Controller Full Code)

## CardIssuanceController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Card\IssueCardRequest;
use App\Services\Card\CardIssuanceService;
use Illuminate\Http\JsonResponse;

class CardIssuanceController extends Controller
{
    public function __construct(
        private readonly CardIssuanceService $cardIssuanceService
    ) {}

    public function issue(IssueCardRequest $request): JsonResponse
    {
        try {
            $result = $this->cardIssuanceService->issue(
                user: $request->user(),
                cardType: $request->validated('card_type'),
                currency: $request->validated('currency'),
                dailyLimit: $request->validated('daily_limit'),
                cardLoad: $request->validated('card_load', 0),
                walletId: $request->validated('wallet_id'),
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $result['card']->id,
                    'type' => $result['card']->card_type,
                    'currency' => $result['card']->currency,
                    'masked_pan' => $result['card']->pan_masked,
                    'expiry_date' => $result['card']->expiry_date->format('m/y'),
                    'cvv' => $result['cvv'],
                    'status' => $result['card']->status,
                    'daily_limit' => $result['card']->daily_limit,
                    'balance' => $result['card']->balance,
                ],
            ], 201);
        } catch (\App\Exceptions\Card\CardIssuanceFailedException $e) {
            return $e->render();
        } catch (\App\Exceptions\Card\CardLimitExceededException $e) {
            return $e->render();
        } catch (\App\Exceptions\Card\CardAlreadyExistsException $e) {
            return $e->render();
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'فشل إصدار البطاقة، حاول مرة أخرى',
            ], 500);
        }
    }
}
```

## API Route

```php
Route::middleware(['auth:api', 'throttle:10,1'])->post('/cards/issue', [CardIssuanceController::class, 'issue']);
```
