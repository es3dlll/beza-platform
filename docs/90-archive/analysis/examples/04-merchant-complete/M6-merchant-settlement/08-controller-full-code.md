# 08 - المتحكم الكامل

```php
<?php
namespace App\Http\Controllers\Api\Merchant;
use App\Http\Controllers\Controller;
use App\Http\Resources\SettlementResource;
use App\Services\Merchant\SettlementService;
use Illuminate\Http\JsonResponse;

class SettlementController extends Controller
{
    public function __construct(private readonly SettlementService $settlementService) {}

    public function store(SettlementRequest $request): JsonResponse {
        $result = $this->settlementService->requestSettlement(
            merchant: $request->user()->merchant,
            currency: $request->input('currency'),
        );
        return response()->json(['success' => true, 'message' => 'تم تقديم طلب التسوية', 'data' => new SettlementResource($result)], 201);
    }

    public function history(Request $request): JsonResponse {
        $settlements = $request->user()->merchant->settlements()->orderByDesc('created_at')->paginate(20);
        return response()->json(['success' => true, 'data' => SettlementResource::collection($settlements)]);
    }

    public function calculate(Request $request): JsonResponse {
        $calc = $this->settlementService->calculateSettlement($request->user()->merchant, $request->input('currency'));
        return response()->json(['success' => true, 'data' => $calc]);
    }
}
```
