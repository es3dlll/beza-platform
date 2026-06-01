# 08 - المتحكم الكامل مع كل سطر (CommodityController)

## CommodityController

```php
<?php
// app/Http/Controllers/Api/CommodityController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommodityBuyRequest;
use App\Http\Requests\CommoditySellRequest;
use App\Http\Resources\CommodityHoldingResource;
use App\Http\Resources\CommodityTransactionResource;
use App\Http\Resources\PriceResource;
use App\Models\CommodityPrice;
use App\Services\CommodityService;
use App\Services\PriceFeedProvider;
use Illuminate\Http\JsonResponse;

class CommodityController extends Controller
{
    public function __construct(
        private readonly CommodityService  $commodityService,
        private readonly PriceFeedProvider  $priceFeed,
    ) {}

    /**
     * GET /api/v1/commodity/prices
     *
     * عرض أسعار الذهب والفضة الحالية
     */
    public function getPrices(): JsonResponse
    {
        $prices = $this->priceFeed->getAllPrices();

        return response()->json([
            'success' => true,
            'data'    => [
                'gold'   => new PriceResource($prices['gold']),
                'silver' => new PriceResource($prices['silver']),
            ],
            'market_open' => $this->priceFeed->isMarketOpen(),
        ]);
    }

    /**
     * POST /api/v1/commodity/buy
     *
     * شراء ذهب أو فضة
     */
    public function buy(CommodityBuyRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->commodityService->executeBuy(
            user:        $user,
            commodity:   $request->input('commodity'),
            amountSpent: (float) $request->input('amount_spent'),
            currency:    $request->input('currency'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم شراء ' . $result['grams'] . ' جرام ' . $result['commodity_name'],
            'data'    => [
                'grams'          => $result['grams'],
                'price_per_gram' => $result['price_per_gram'],
                'total_spent'    => $result['total_spent'],
                'fee'            => $result['fee'],
                'commodity'      => $result['commodity'],
                'holding'        => new CommodityHoldingResource($result['holding']),
                'new_balance'    => $result['new_balance'],
                'reference'      => $result['reference_number'],
            ],
        ], 201);
    }

    /**
     * POST /api/v1/commodity/sell
     *
     * بيع ذهب أو فضة
     */
    public function sell(CommoditySellRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->commodityService->executeSell(
            user:      $user,
            commodity: $request->input('commodity'),
            grams:     (float) $request->input('grams'),
            currency:  $request->input('currency'),
        );

        return response()->json([
            'success' => true,
            'message' => 'تم بيع ' . $result['grams'] . ' جرام ' . $result['commodity_name'],
            'data'    => [
                'grams'          => $result['grams'],
                'price_per_gram' => $result['price_per_gram'],
                'total_received' => $result['total_received'],
                'fee'            => $result['fee'],
                'commodity'      => $result['commodity'],
                'holding'        => new CommodityHoldingResource($result['holding']),
                'new_balance'    => $result['new_balance'],
                'reference'      => $result['reference_number'],
            ],
        ], 200);
    }

    /**
     * GET /api/v1/commodity/holdings
     *
     * عرض محفظة المستخدم من الذهب والفضة مع الأرباح/الخسائر
     */
    public function getHoldings(): JsonResponse
    {
        $user = request()->user();

        $holdings = $user->commodityHoldings()
            ->orderBy('commodity')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => CommodityHoldingResource::collection($holdings),
        ]);
    }

    /**
     * GET /api/v1/commodity/history
     *
     * سجل معاملات المستخدم
     */
    public function getHistory(): JsonResponse
    {
        $user = request()->user();

        $transactions = $user->commodityTransactions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => CommodityTransactionResource::collection($transactions),
            'meta'    => [
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'total'        => $transactions->total(),
            ],
        ]);
    }
}
```

## CommodityHoldingResource

```php
<?php
// app/Http/Resources/CommodityHoldingResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommodityHoldingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'commodity'           => $this->commodity,
            'grams'               => (float) $this->grams,
            'avg_price_usd'       => (float) $this->avg_price_usd,
            'total_invested_usd'  => (float) $this->total_invested_usd,
            'current_value_usd'   => $this->current_value_usd,
            'profit_loss'         => $this->profit_loss,
            'profit_loss_percent' => $this->profit_loss_percent,
            'updated_at'          => $this->updated_at->toIso8601String(),
        ];
    }
}
```

## CommodityTransactionResource

```php
<?php
// app/Http/Resources/CommodityTransactionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommodityTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'reference_number' => $this->reference_number,
            'commodity'        => $this->commodity,
            'type'             => $this->type,
            'grams'            => (float) $this->grams,
            'price_usd'        => (float) $this->price_usd,
            'total_usd'        => (float) $this->total_usd,
            'fee'              => (float) $this->fee,
            'status'           => $this->status,
            'created_at'       => $this->created_at->toIso8601String(),
        ];
    }
}
```

## PriceResource

```php
<?php
// app/Http/Resources/PriceResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'price_usd'  => (float) $this['price_usd'],
            'price_syp'  => (float) $this['price_syp'],
            'bid'        => (float) $this['bid'],
            'ask'        => (float) $this['ask'],
            'change_24h' => $this['change_24h'] ?? 0,
            'timestamp'  => $this['timestamp'],
        ];
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\CommodityController;

Route::middleware(['auth:api', 'throttle:30,1'])->group(function () {
    Route::get('/commodity/prices',  [CommodityController::class, 'getPrices']);
    Route::post('/commodity/buy',    [CommodityController::class, 'buy']);
    Route::post('/commodity/sell',   [CommodityController::class, 'sell']);
    Route::get('/commodity/holdings',[CommodityController::class, 'getHoldings']);
    Route::get('/commodity/history', [CommodityController::class, 'getHistory']);
});
```
