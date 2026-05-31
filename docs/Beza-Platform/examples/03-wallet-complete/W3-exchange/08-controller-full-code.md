# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## ExchangeController

```php
<?php
// app/Http/Controllers/Api/ExchangeController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExchangeRequest;
use App\Http\Resources\ExchangeResource;
use App\Services\ExchangeService;
use Illuminate\Http\JsonResponse;

class ExchangeController extends Controller
{
    public function __construct(
        private readonly ExchangeService $exchangeService
    ) {}

    /**
     * POST /api/v1/wallet/exchange
     *
     * تحويل أموال بين محفظة SYP و USD (صرافة)
     *
     * @param ExchangeRequest $request
     * @return JsonResponse
     */
    public function exchange(ExchangeRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->exchangeService->exchange(
            user:           $user,
            fromCurrency:   $request->input('from_currency'),
            toCurrency:     $request->input('to_currency'),
            amount:         (float) $request->input('amount'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تمت الصرافة بنجاح',
            'data'    => new ExchangeResource($result),
        ]);
    }
}
```

## ExchangeResource

```php
<?php
// app/Http/Resources/ExchangeResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource; // array from ExchangeService

        return [
            'transaction' => [
                'id'               => $data['transaction']->id,
                'reference_number' => $data['transaction']->reference_number,
                'type'             => 'exchange',
                'status'           => 'completed',
                'from_currency'    => $data['from_currency'],
                'to_currency'      => $data['to_currency'],
                'amount'           => (float) $data['transaction']->amount,
                'converted_amount' => $data['converted_amount'],
                'fee'              => (float) $data['transaction']->fee,
                'rate'             => $data['rate'],
                'fee_percentage'   => $data['fee_percentage'],
                'completed_at'     => $data['transaction']->completed_at->toIso8601String(),
            ],
            'new_balances' => [
                'syp' => (float) $data['new_balances']['syp'],
                'usd' => (float) $data['new_balances']['usd'],
            ],
        ];
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\ExchangeController;

Route::middleware(['auth:api', 'throttle:20,1'])->group(function () {
    Route::post('/wallet/exchange', [ExchangeController::class, 'exchange']);
});
```

## مثال الاستجابة

### نجاح (200)
```json
{
    "success": true,
    "message": "تمت الصرافة بنجاح",
    "data": {
        "transaction": {
            "id": 55,
            "reference_number": "BZ270526143200A1B2C3",
            "type": "exchange",
            "status": "completed",
            "from_currency": "SYP",
            "to_currency": "USD",
            "amount": 100000.00,
            "converted_amount": 7.58,
            "fee": 1500.00,
            "rate": 13000.00,
            "fee_percentage": 1.50,
            "completed_at": "2026-05-27T14:32:00+03:00"
        },
        "new_balances": {
            "syp": 48500.00,
            "usd": 507.58
        }
    }
}
```

### فشل (422) — رصيد غير كافٍ
```json
{
    "success": false,
    "message": "رصيد غير كافٍ",
    "errors": {
        "balance": ["رصيد المحفظة غير كافٍ لإتمام عملية الصرافة. المتاح: 100000، المطلوب: 101500"]
    }
}
```

### فشل (422) — عملة واحدة
```json
{
    "success": false,
    "message": "لا يمكن الصرافة لنفس العملة",
    "errors": {
        "currencies": ["يجب أن تختلف عملة المصدر عن عملة الوجهة"]
    }
}
```
