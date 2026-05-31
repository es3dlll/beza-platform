# 19 - حالات الحافة (Edge Cases)

## السيناريوهات (Scenarios)
1. **صورة كبيرة جدا**: Validation → max 2MB لكل صورة.
2. **منتج بدون مخزون (خدمة)**: stock = null → مخزون غير محدود.
3. **آخر قطعة في المخزون**: UPDATE WHERE stock >= 1 → يمنع البيع بعد النفاد.
4. **حذف منتج عليه طلبات**: Soft delete أو منع الحذف إذا كان pending.
5. **تعديل سعر منتج بعد نشره**: تخزين السعر القديم في metadata.
6. **رفع أكثر من 5 صور**: Validation يمنع تجاوز الحد الأقصى.
7. **منتج بنفس الاسم لنفس التاجر**: unique constraint على (merchant_id, name).

## جدول حالات الحافة
| # | الحالة | النتيجة |
|---|--------|---------|
| 1 | صورة > 2MB | رفض الرفع |
| 2 | stock = null | مخزون غير محدود |
| 3 | آخر قطعة | بيع آمن |
| 4 | حذف منتج | حذف مع الصور |
| 5 | إنشاء بدون صورة | مسموح |
| 6 | أكثر من 5 صور | رفض (422) |
| 7 | اسم منتج مكرر | رفض (422) |

## كود معالجة حالات الحافة (Edge Case Handling Code)

### 1. التحقق من حجم الصورة ونوعها
```php
'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
'images'   => ['nullable', 'array', 'max:5'],
```

### 2. معالجة المخزون غير المحدود والبيع الآمن
```php
public function hasStock(): bool
{
    return is_null($this->stock) || $this->stock > 0;
}

public function decrementStock(int $qty = 1): void
{
    if (!is_null($this->stock)) {
        $affected = DB::update(
            'UPDATE merchant_products SET stock = stock - ? WHERE id = ? AND stock >= ?',
            [$qty, $this->id, $qty]
        );
        if ($affected === 0) {
            throw new InsufficientStockException();
        }
    }
}
```

### 3. منع تكرار اسم المنتج لنفس التاجر
```php
$exists = MerchantProduct::where('merchant_id', $merchantId)
    ->where('name', $name)
    ->exists();

if ($exists) {
    throw new DuplicateProductException('اسم المنتج موجود مسبقاً لمتجرك');
}
```

### 4. حذف منتج مع الصور المرتبطة
```php
public function deleteProduct(Merchant $merchant, int $productId): void
{
    $product = MerchantProduct::where('merchant_id', $merchant->id)
        ->findOrFail($productId);

    // حذف الصور من التخزين أولاً
    foreach ($product->images as $image) {
        Storage::disk('public')->delete($image->image_path);
    }

    // حذف المنتج (cascade يحذف الصور من DB تلقائياً)
    $product->delete();
}
```

## ملخص
هذه الحالات تغطي السيناريوهات الأكثر شيوعاً في إدارة المنتجات. المعالجة الصحيحة لكل حالة تمنع حدوث أخطاء في الإنتاج وتحسن تجربة المستخدم.
