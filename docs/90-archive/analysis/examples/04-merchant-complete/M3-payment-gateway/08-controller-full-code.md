# 08 - كود المتحكم الكامل (Controller Full Code)

```php
<?php
namespace App\Http\Controllers\Api\Merchant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\PaymentLinkRequest;
use App\Http\Resources\PaymentLinkResource;
use App\Services\Merchant\PaymentLinkService;
use Illuminate\Http\JsonResponse;

class PaymentLinkController extends Controller
{
    public function __construct(private readonly PaymentLinkService $linkService) {}

    public function store(PaymentLinkRequest $request): JsonResponse {
        $link = $this->linkService->create(
            merchant: $request->user()->merchant,
            amount: $request->input('amount'),
            currency: $request->input('currency'),
            description: $request->input('description'),
            redirectUrl: $request->input('redirect_url'),
            expiryHours: $request->input('expiry_hours'),
        );
        return response()->json(['success' => true, 'message' => 'تم إنشاء رابط الدفع', 'data' => new PaymentLinkResource($link)], 201);
    }

    public function show($token): JsonResponse {
        return response()->json(['success' => true, 'data' => new PaymentLinkResource($this->linkService->findByToken($token))]);
    }

    public function cancel($id): JsonResponse {
        $this->linkService->cancel($id);
        return response()->json(['success' => true, 'message' => 'تم إلغاء رابط الدفع']);
    }
}
```
