# 14 - ACID + الأقفال + حالات السباق (ACID + Locks + Race Conditions)

## ضمان ACID
```php
DB::transaction(function () {
    $product = MerchantProduct::create([...]);  // فشل → ROLLBACK
    foreach ($images as $image) {
        ProductImage::create([...]);  // فشل → ROLLBACK للمنتج أيضاً
    }
}, attempts: 3);
```

هذا يضمن أن المنتج والصور يتم حفظهم معاً في خطوة واحدة ذرية. إذا فشل حفظ أي صورة، يتم التراجع عن إنشاء المنتج بالكامل.

## معالجة المخزون (Stock Management)
```php
// تحديث آمن للمخزون يمنع البيع بعد النفاد
DB::update('UPDATE merchant_products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$qty, $id, $qty]);
```

استخدام `WHERE stock >= ?` يضمن عدم بيع كمية أكبر من المتاح. هذه عملية ذرية على مستوى قاعدة البيانات تمنع البيع الزائد.

## Race Conditions في المخزون
بدون قفل: طلبان متزامنان لشراء آخر قطعة → بيع القطعة لشخصين.
الحل: استخدام UPDATE الشرطي (WHERE stock >= ?) داخل transaction.

## Row Lock مثال (Pessimistic Locking)
```php
DB::transaction(function () use ($productId, $qty) {
    // قفل الصف لمنع أي تعديل آخر
    $product = MerchantProduct::where('id', $productId)
        ->lockForUpdate()
        ->first();

    if (!$product || $product->stock < $qty) {
        throw new InsufficientStockException();
    }

    $product->decrement('stock', $qty);
});
```

## شرح ACID بالتفصيل
- **Atomicity**: المنتج والصور يتم إنشاؤهم معاً أو لا أحد. تستخدم DB::transaction لضمان ذلك.
- **Consistency**: unique constraints على (merchant_id + name) تمنع تكرار المنتجات. Foreign keys تضمن سلامة العلاقات.
- **Isolation**: كل transaction مستقلة عن الأخرى. `lockForUpdate()` يمنع حدوث race conditions بين طلبين متزامنين.
- **Durability**: بمجرد تأكيد commit، البيانات مخزنة بشكل دائم في MySQL InnoDB وتصمد حتى عند انقطاع الكهرباء.

## مثال كامل لإنشاء منتج آمن
```php
public function createProduct(Merchant $merchant, array $data): MerchantProduct
{
    return DB::transaction(function () use ($merchant, $data) {
        // التحقق من عدم تكرار الاسم لنفس التاجر
        $exists = MerchantProduct::where('merchant_id', $merchant->id)
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            throw new DuplicateProductException();
        }

        return $merchant->products()->create($data);
    }, attempts: 3);
}
```

هذا المثال يوضح كيفية الجمع بين التحقق من التكرار وإنشاء المنتج داخل transaction واحدة مع إعادة المحاولة عند فشل القفل (deadlock).
