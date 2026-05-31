# 01 - فكرة المشروع (Business Idea) - منتجات التاجر (Merchant Products)

## الفكرة الأساسية
تاجر لديه متجر إلكتروني يريد إضافة منتجاته إلى منصة Beza ليتمكن العملاء من شرائها عبر بوابة الدفع. هذه العملية تسمح للتاجر بإدارة كتالوج المنتجات الخاص به بشكل كامل، مع دعم أسعار مزدوجة (SYP/USD) وإمكانية رفع صور متعددة لكل منتج.

## سيناريو المستخدم
```
بصفتي: تاجر في Beza
أريد: إدارة منتجاتي (إضافة/تعديل/حذف)
لكي: أعرضها للعملاء للشراء عبر بوابة الدفع
```

## قبول السيناريو (Acceptance Criteria)
| # | الشرط | الحالة |
|---|-------|--------|
| 1 | إضافة منتج جديد (name, price SYP+USD) | إجباري |
| 2 | رفع صورة للمنتج | اختياري |
| 3 | تفعيل/تعطيل المنتج (is_active) | إجباري |
| 4 | إدارة المخزون (stock) | اختياري |
| 5 | التحقق من أن المنتج يتبع للتاجر | أمني |
| 6 | تعديل/حذف المنتج | CRUD |

## تفاصيل العملية
إدارة المنتجات هي عملية CRUD كاملة تتيح للتاجر إضافة منتجاته وعرضها وتحديثها وحذفها. كل منتج له سعر بالليرة السورية وسعر بالدولار الأمريكي، مما يسمح للعملاء بالاختيار بين العملتين. يمكن للتاجر أيضاً التحكم في حالة المنتج (نشط/غير نشط) وإدارة المخزون المتاح.

## مثال على نموذج المنتج (Product Model Example)
```php
<?php
namespace App\Models;

class MerchantProduct extends Model
{
    protected $fillable = [
        'merchant_id', 'name', 'description',
        'price_syp', 'price_usd', 'category',
        'stock', 'is_active',
    ];

    protected $casts = [
        'price_syp' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
```

## مثال على إنشاء منتج (Create Product Example)
```php
$product = MerchantProduct::create([
    'merchant_id' => $merchant->id,
    'name' => 'هاتف ذكي',
    'description' => 'هاتف يعمل بنظام أندرويد',
    'price_syp' => 2500000,
    'price_usd' => 200,
    'category' => 'electronics',
    'stock' => 50,
    'is_active' => true,
]);
```

هذا المثال يوضح كيفية إنشاء منتج جديد في قاعدة البيانات. المنتج يرتبط بالتاجر عبر merchant_id، ويدعم أسعاراً بالعملتين مع إمكانية تحديد المخزون والتصنيف.
