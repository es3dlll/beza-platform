<?php

declare(strict_types=1);

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Exceptions\ProductNotFoundException;
use Modules\Marketplace\Models\Product;
use Modules\Marketplace\Models\ProductCategory;

final class CatalogService
{
    public function listCategories(): iterable
    {
        return ProductCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function listProducts(?string $categoryId = null, ?string $vendorId = null): iterable
    {
        $query = Product::where('is_active', true);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        if ($vendorId !== null) {
            $query->where('vendor_id', $vendorId);
        }

        return $query->with(['vendor', 'category'])->get();
    }

    public function findProduct(string $id): Product
    {
        return Product::with(['vendor', 'category'])->findOrFail($id);
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function updateProduct(string $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);

        return $product->fresh();
    }
}
