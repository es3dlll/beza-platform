# 08 - المتحكم الكامل مع كل سطر (Controller Full Code)

## BalanceController

```php
<?php
// app/Http/Controllers/Api/BalanceController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BalanceResource;
use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function __construct(
        private readonly BalanceService $balanceService
    ) {}

    /**
     * GET /api/v1/wallet/balance
     *
     * عرض رصيد المستخدم الحالي لكل العملات
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $balance = $this->balanceService->getBalance($user);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الرصيد بنجاح',
            'data'    => new BalanceResource($balance),
        ]);
    }
}
```

## BalanceResource

```php
<?php
// app/Http/Resources/BalanceResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $wallets = $this->resource; // array من المحافظ

        $syp = collect($wallets)->firstWhere('currency', 'SYP');
        $usd = collect($wallets)->firstWhere('currency', 'USD');

        return [
            'syp' => $syp ? [
                'balance'        => (float) $syp['balance'],
                'frozen'         => (float) $syp['frozen_balance'],
                'available'      => (float) $syp['balance'] - (float) $syp['frozen_balance'],
                'wallet_number'  => $syp['wallet_number'],
            ] : null,
            'usd' => $usd ? [
                'balance'        => (float) $usd['balance'],
                'frozen'         => (float) $usd['frozen_balance'],
                'available'      => (float) $usd['balance'] - (float) $usd['frozen_balance'],
                'wallet_number'  => $usd['wallet_number'],
            ] : null,
        ];
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\BalanceController;

Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
    Route::get('/wallet/balance', [BalanceController::class, 'index']);
});
```

## مثال الاستجابة

### نجاح (200)
```json
{
    "success": true,
    "data": {
        "syp": {
            "balance": 150000.00,
            "frozen": 5000.00,
            "available": 145000.00,
            "wallet_number": "621234567890"
        },
        "usd": {
            "balance": 500.00,
            "frozen": 0.00,
            "available": 500.00,
            "wallet_number": "631234567890"
        }
    }
}
```

### خطأ مصادقة (401)
```json
{
    "message": "Unauthenticated"
}
```

### محافظ غير موجودة (404)
```json
{
    "success": false,
    "message": "لم يتم العثور على محافظ",
    "errors": {
        "wallets": ["المستخدم الحالي ليس لديه محافظ"]
    }
}
```
