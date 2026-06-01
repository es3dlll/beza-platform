# 03 - تدفق البيانات (Data Flow Sequence) - منتجات التاجر (Merchant Products)

```
  Merchant      Flutter/React      Laravel API        ProductService     MySQL         Storage
     │                │                  │                  │               │              │
     │  إضافة         │                  │                  │               │              │
     │--------------->│                  │                  │               │              │
     │                │  POST /products  │                  │               │              │
     │                │----------------->│                  │               │              │
     │                │                  │  Validate        │               │              │
     │                │                  │----------------->│               │              │
     │                │                  │  Check merchant  │-------------->│              │
     │                │                  │  Upload images   │-------------->│------------->│
     │                │                  │  INSERT product  │-------------->│              │
     │                │ Response 201     │                  │               │              │
     │                │<-----------------│                  │               │              │
     │<---------------│                  │                  │               │              │
```

## شرح التدفق (Flow Explanation)
1. التاجر يملأ نموذج إضافة منتج في الواجهة الأمامية
2. يتم إرسال البيانات عبر POST /api/v1/merchant/products
3. ProductRequest يقوم بالتحقق من صحة البيانات
4. ProductService ينشئ المنتج مع الصور في transaction واحد
5. يتم تخزين الصور في storage والتأكد من ownership
6. يعود الرد مع بيانات المنتج الجديد

## كود التحقق من الصحة (Validation Code)
```php
<?php
namespace App\Http\Requests\Merchant;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'price_syp' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'price_usd' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'stock'     => ['nullable', 'integer', 'min:0', 'max:999999'],
            'images.*'  => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
```

## كود الخدمة مع المعاملة (Service with Transaction)
```php
<?php
namespace App\Services\Merchant;

class ProductService
{
    public function create(Merchant $merchant, array $data, array $images = []): MerchantProduct
    {
        return DB::transaction(function () use ($merchant, $data, $images) {
            $product = $merchant->products()->create([
                'name'     => $data['name'],
                'price_syp' => $data['price_syp'],
                'price_usd' => $data['price_usd'],
                'stock'    => $data['stock'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($images as $image) {
                $path = $image->store("products/{$product->id}", 'public');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => !$product->images()->exists(),
                ]);
            }

            return $product;
        }, attempts: 3);
    }
}
```

هذا التدفق يضمن أن جميع عمليات إنشاء المنتج تتم داخل معاملة واحدة لضمان Atomicity: إذا فشلت أي خطوة، يتم التراجع عن جميع التغييرات.
