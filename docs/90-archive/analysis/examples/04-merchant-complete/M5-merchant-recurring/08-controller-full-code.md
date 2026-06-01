# 08 - المتحكم الكامل

```php
<?php
namespace App\Http\Controllers\Api\Merchant;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Services\Merchant\SubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subService) {}

    public function store(SubscriptionRequest $request): JsonResponse {
        $sub = $this->subService->create(
            merchant: $request->user()->merchant,
            customerPhone: $request->input('customer_phone'),
            amount: $request->input('amount'),
            currency: $request->input('currency'),
            interval: $request->input('interval'),
            description: $request->input('description'),
            maxCycles: $request->input('max_cycles'),
        );
        return response()->json(['success' => true, 'message' => 'تم إنشاء الاشتراك، في انتظار موافقة العميل', 'data' => new SubscriptionResource($sub)], 201);
    }

    public function index(Request $request): JsonResponse {
        $subs = $request->user()->merchant->subscriptions()->with('customer', 'charges')->paginate(20);
        return response()->json(['success' => true, 'data' => SubscriptionResource::collection($subs)]);
    }

    public function cancel($id): JsonResponse {
        $this->subService->cancel($id);
        return response()->json(['success' => true, 'message' => 'تم إلغاء الاشتراك']);
    }
}
```
