# 10 - طبقة الخدمة المساعدة (Service Layer - Auxiliary)

## ProductImageService كامل
```php
<?php
namespace AppServicesMerchant;
use AppModelsMerchantProduct;
use AppModelsProductImage;
use IlluminateSupportFacadesStorage;

class ProductImageService
{
    public function setPrimary(MerchantProduct $product, int $imageId): void {
        $product->images()->update(['is_primary' => false]);
        $product->images()->findOrFail($imageId)->update(['is_primary' => true]);
    }

    public function deleteImage(MerchantProduct $product, int $imageId): void {
        $image = $product->images()->findOrFail($imageId);
        Storage::disk('public')->delete($image->image_path);
        $image->delete();
    }

    public function deleteAllImages(MerchantProduct $product): void {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        $product->images()->delete();
    }

    public function reorderImages(MerchantProduct $product, array $order): void {
        foreach ($order as $index => $imageId) {
            ProductImage::where('id', $imageId)->where('product_id', $product->id)
                ->update(['sort_order' => $index]);
        }
    }
}
```

## شرح الخدمة
- setPrimary: تعيين صورة أساسية بعد إزالة الصورة الأساسية السابقة
- deleteImage: حذف الصورة من التخزين ثم من قاعدة البيانات
- deleteAllImages: حذف جميع صور المنتج عند حذف المنتج
- reorderImages: إعادة ترتيب الصور حسب مصفوفة ids
