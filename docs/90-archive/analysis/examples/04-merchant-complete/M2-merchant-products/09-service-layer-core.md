# 09 - طبقة الخدمة الأساسية (Service Layer - Core)

```php
<?php
namespace App\Services\Merchant;
use App\Models\Merchant;
use App\Models\MerchantProduct;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function create(Merchant $merchant, array $data, array $images = []): MerchantProduct
    {
        return DB::transaction(function () use ($merchant, $data, $images) {
            $product = $merchant->products()->create([
                'name' => $data['name'], 'description' => $data['description'] ?? null,
                'price_syp' => $data['price_syp'], 'price_usd' => $data['price_usd'],
                'category' => $data['category'] ?? null, 'stock' => $data['stock'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
            foreach ($images as $image) {
                $path = $image->store("products/{$product->id}", 'public');
                $product->images()->create(['image_path' => $path, 'is_primary' => !$product->images()->exists(), 'sort_order' => 0]);
            }
            return $product;
        }, attempts: 3);
    }

    public function update(Merchant $merchant, int $productId, array $data, array $images = []): MerchantProduct {
        $product = $this->findForMerchant($merchant->id, $productId);
        DB::transaction(function () use ($product, $data, $images) {
            $product->update($data);
            foreach ($images as $image) {
                $path = $image->store("products/{$product->id}", 'public');
                $product->images()->create(['image_path' => $path, 'is_primary' => false, 'sort_order' => $product->images()->count()]);
            }
        });
        return $product->fresh()->load('images');
    }

    public function delete(Merchant $merchant, int $productId): void {
        $product = $this->findForMerchant($merchant->id, $productId);
        foreach ($product->images as $image) { Storage::disk('public')->delete($image->image_path); }
        $product->delete();
    }

    public function findForMerchant(int $merchantId, int $productId): MerchantProduct {
        return MerchantProduct::where('merchant_id', $merchantId)->findOrFail($productId);
    }
}
```
