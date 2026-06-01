# 08 - كود المتحكم الكامل (Controller Full Code)

```php
<?php
namespace App\Http\Controllers\Api\Merchant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\ProductRequest;
use App\Http\Resources\MerchantProductResource;
use App\Services\Merchant\ProductService;
use Illuminate\Http\JsonResponse;

class MerchantProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): JsonResponse {
        $merchant = $request->user()->merchant;
        $products = $merchant->products()->with('primaryImage')
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->paginate(20);
        return response()->json(['success' => true, 'data' => MerchantProductResource::collection($products)]);
    }

    public function store(ProductRequest $request): JsonResponse {
        $product = $this->productService->create(
            merchant: $request->user()->merchant,
            data: $request->validated(),
            images: $request->file('images', []),
        );
        return response()->json(['success' => true, 'message' => 'تم إضافة المنتج', 'data' => new MerchantProductResource($product)], 201);
    }

    public function show(Request $request, $id): JsonResponse {
        $product = $this->productService->findForMerchant($request->user()->merchant->id, $id);
        return response()->json(['success' => true, 'data' => new MerchantProductResource($product->load('images'))]);
    }

    public function update(ProductRequest $request, $id): JsonResponse {
        $product = $this->productService->update($request->user()->merchant, $id, $request->validated(), $request->file('images', []));
        return response()->json(['success' => true, 'message' => 'تم تحديث المنتج', 'data' => new MerchantProductResource($product)]);
    }

    public function destroy(Request $request, $id): JsonResponse {
        $this->productService->delete($request->user()->merchant, $id);
        return response()->json(['success' => true, 'message' => 'تم حذف المنتج']);
    }
}
```
