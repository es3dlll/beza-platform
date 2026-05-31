# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## Controller

```php
<?php
// app/Http/Controllers/Api/RequestMoneyController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestMoneyRequest;
use App\Http\Resources\TransactionResource;
use App\Services\RequestMoneyService;
use Illuminate\Http\JsonResponse;

class RequestMoneyController extends Controller
{
    public function __construct(
        private readonly RequestMoneyService $service
    ) {}

    public function execute(RequestMoneyRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->service->process(
            user: $user,
            amount: (float) $request->input('amount'),
            currency: $request->input('currency'),
            pin: $request->input('pin'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تمت العملية بنجاح',
            'data'    => [
                'transaction' => new TransactionResource($result['transaction']),
                'new_balance' => $result['new_balance'],
            ],
        ], 201);
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\RequestMoneyController;

Route::middleware(['auth:api', 'throttle:30,1'])->group(function () {
    Route::post('/transfer/request', ...)
    Route::post('/transfer/request/{id}/accept', ...)
    Route::post('/transfer/request/{id}/reject', ...)
});
```

## مثال الاستجابة

```json
{
    "success": true,
    "message": "تمت العملية بنجاح",
    "data": {
        "transaction": {
            "id": 42,
            "reference_number": "BZ260527143200A1B2C3",
            "type": "money_request",
            "status": "completed",
            "amount": 100.00,
            "fee": 0.00,
            "created_at": "2026-05-27T14:32:00+03:00",
            "completed_at": "2026-05-27T14:32:00+03:00"
        },
        "new_balance": 400.00
    }
}
```
