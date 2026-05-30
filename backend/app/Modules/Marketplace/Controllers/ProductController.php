<?php

declare(strict_types=1);

namespace Modules\Marketplace\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Marketplace\Services\CatalogService;
use App\Support\ApiResponse;

final class ProductController extends Controller
{
    use ApiResponse;
    public function __construct(
        private CatalogService $catalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->catalog->listProducts(
            $request->query('category_id'),
            $request->query('vendor_id'),
        );

        return $this->respond($products);
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->catalog->findProduct($id);

        return $this->respond($product);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id' => 'required|string',
            'category_id' => 'required|string',
            'name' => 'required|string|max:100',
            'name_ar' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|max:20',
            'price' => 'required|integer|min:0',
            'stock' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
            'metadata' => 'nullable|json',
        ]);

        $product = $this->catalog->createProduct($data);

        return $this->respondCreated($product);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'vendor_id' => 'sometimes|string',
            'category_id' => 'sometimes|string',
            'name' => 'sometimes|string|max:100',
            'name_ar' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|max:20',
            'price' => 'sometimes|integer|min:0',
            'stock' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
            'metadata' => 'nullable|json',
        ]);

        $product = $this->catalog->updateProduct($id, $data);

        return $this->respond($product);
    }
}
