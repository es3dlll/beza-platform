# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## TransferController

```php
<?php
// app/Http/Controllers/Api/TransferController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransferService $transferService
    ) {}

    /**
     * POST /api/v1/transfer
     *
     * تحويل أموال من المستخدم الحالي إلى مستخدم آخر
     *
     * @param TransferRequest $request
     * @return JsonResponse
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        $fromUser = $request->user();

        $result = $this->transferService->transfer(
            fromUser: $fromUser,
            toPhone:  $request->input('to_phone'),
            amount:   (float) $request->input('amount'),
            currency: $request->input('currency'),
            pin:      $request->input('pin'),
            description: $request->input('description'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم التحويل بنجاح',
            'data'    => [
                'transaction' => new TransactionResource($result['transaction']),
                'new_balance' => $result['new_balance'],
            ],
        ], 201);
    }
}
```

## TransactionResource

```php
<?php
// app/Http/Resources/TransactionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'reference_number' => $this->reference_number,
            'type'             => $this->type,
            'status'           => $this->status,
            'amount'           => (float) $this->amount,
            'amount_in_usd'    => (float) $this->amount_in_usd,
            'currency'         => $this->fromWallet?->currency ?? $this->toWallet?->currency,
            'fee'              => (float) $this->fee,
            'description'      => $this->description,
            'sender'           => $this->when($this->fromWallet, [
                'id'    => $this->fromWallet?->user?->id,
                'name'  => $this->fromWallet?->user?->name,
                'phone' => $this->fromWallet?->user?->phone,
            ]),
            'receiver'         => $this->when($this->toWallet, [
                'id'    => $this->toWallet?->user?->id,
                'name'  => $this->toWallet?->user?->name,
                'phone' => $this->toWallet?->user?->phone,
            ]),
            'created_at'       => $this->created_at->toIso8601String(),
            'completed_at'     => $this->completed_at?->toIso8601String(),
        ];
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\TransferController;

Route::middleware(['auth:api', 'throttle:30,1'])->group(function () {
    Route::post('/transfer', [TransferController::class, 'transfer']);
});
```

## مثال الاستجابة

### نجاح (201)
```json
{
    "success": true,
    "message": "تم التحويل بنجاح",
    "data": {
        "transaction": {
            "id": 42,
            "reference_number": "BZ260527143200A1B2C3",
            "type": "transfer",
            "status": "completed",
            "amount": 100.00,
            "amount_in_usd": 100.00,
            "currency": "USD",
            "fee": 0.00,
            "description": "مصروف أخوي",
            "sender": {
                "id": 1,
                "name": "أحمد",
                "phone": "963944123456"
            },
            "receiver": {
                "id": 2,
                "name": "محمد",
                "phone": "963944654321"
            },
            "created_at": "2026-05-27T14:32:00+03:00",
            "completed_at": "2026-05-27T14:32:00+03:00"
        },
        "new_balance": 400.00
    }
}
```

### فشل (422)
```json
{
    "success": false,
    "message": "رصيد غير كافٍ",
    "errors": {
        "balance": ["رصيد المحفظة غير كافٍ لإتمام العملية"]
    }
}
```

### خطأ مصادقة (401)
```json
{
    "message": "Unauthenticated"
}
```
