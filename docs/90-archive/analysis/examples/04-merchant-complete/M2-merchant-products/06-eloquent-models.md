# 06 - نماذج Eloquent (Eloquent Models)

## MerchantProduct Model
```php
<?php
namespace AppModels;
use IlluminateDatabaseEloquentModel;

class MerchantProduct extends Model
{
    protected $table = 'merchant_products';
    protected $fillable = ['merchant_id', 'name', 'description', 'price_syp', 'price_usd', 'category', 'stock', 'is_active'];
    protected $casts = ['price_syp' => 'decimal:2', 'price_usd' => 'decimal:2', 'stock' => 'integer', 'is_active' => 'boolean'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function images() { return $this->hasMany(ProductImage::class); }
    public function primaryImage() { return $this->hasOne(ProductImage::class)->where('is_primary', true); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeByMerchant($q, $merchantId) { return $q->where('merchant_id', $merchantId); }
    public function scopeInStock($q) { return $q->whereNull('stock')->orWhere('stock', '>', 0); }
    public function hasStock(): bool { return is_null($this->stock) || $this->stock > 0; }
    public function decrementStock(int $qty = 1): void {
        if (!is_null($this->stock)) { $this->decrement('stock', $qty); }
    }
}
```

## ProductImage Model
```php
class ProductImage extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_id', 'image_path', 'is_primary', 'sort_order'];
    public function product() { return $this->belongsTo(MerchantProduct::class); }
}
```

## شرح الموديلات
- MerchantProduct: الموديل الرئيسي مع العلاقات والسكوبس المساعدة
- ProductImage: موديل بسيط بدون timestamps لأنها غير ضرورية
- العلاقات: belongsTo → merchant, hasMany → images
- السكوبس: active, byMerchant, inStock لتسهيل الاستعلامات
