# 08 - المتحكم الكامل

```php
<?php
namespace App\Http\Controllers\Api\Merchant;
use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantOrderResource;
use App\Services\Merchant\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantOrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse {
        $merchant = $request->user()->merchant;
        $orders = $merchant->orders()
            ->with('items', 'customer')
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->date_from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->orderByDesc('created_at')
            ->paginate(20);
        return response()->json(['success' => true, 'data' => MerchantOrderResource::collection($orders)]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, $id): JsonResponse {
        $this->orderService->updateStatus($request->user()->merchant->id, $id, $request->input('status'), $request->input('notes'));
        return response()->json(['success' => true, 'message' => 'تم تحديث حالة الطلب']);
    }
}
```
