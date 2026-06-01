# 06 - موديل SystemSetting مع تحويل الأنواع التلقائي (Eloquent Model)

## ملف الموديل الكامل (Complete Model File)

```php
<?php
// // ملف: app/Models/SystemSetting.php
// // موديل إعدادات النظام مع تحويل تلقائي للقيم حسب النوع
// // لا توجد علاقات مع موديلات أخرى

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    // // اسم الجدول في قاعدة البيانات
    protected $table = 'system_settings';

    // // الحقول القابلة للتعبئة الجماعية
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
    ];

    // // تحويلات الأنواع التلقائية (Laravel casts)
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // // الحقول المخفية عند تحويل الموديل إلى JSON
    protected $hidden = [
        'id',
    ];

    // ================================================================
    //  دوال تحويل القيمة حسب النوع (Value Type Casting)
    // ================================================================

    /**
     * // تحويل القيمة المخزنة إلى النوع المناسب
     * // مثلاً: value="1", type="boolean" -> true
     * // مثلاً: value='{"key":"val"}', type="json" -> ['key' => 'val']
     */
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float'   => (float) $this->value,
            'json'    => json_decode($this->value, true) ?? [],
            default   => (string) $this->value, // string
        };
    }

    /**
     * // تحويل القيمة من النوع المناسب إلى نص للتخزين
     */
    public function setTypedValue(mixed $value): void
    {
        $this->value = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'float'   => (string) (float) $value,
            'json'    => json_encode($value, JSON_UNESCAPED_UNICODE),
            default   => (string) $value,
        };
    }

    /**
     * // الحصول على المفتاح الكامل (group.key)
     * // مثلاً: "general.app_name"
     */
    public function getFullKeyAttribute(): string
    {
        return "{$this->group}.{$this->key}";
    }

    /**
     * // هل هذا الإعداد من نوع منطقي؟
     */
    public function isBoolean(): bool
    {
        return $this->type === 'boolean';
    }

    /**
     * // هل هذا الإعداد من نوع JSON؟
     */
    public function isJson(): bool
    {
        return $this->type === 'json';
    }

    // ================================================================
    //  دوال إحصائية (Scopes)
    // ================================================================

    /**
     * // تصفية حسب مجموعة الإعدادات
     */
    public function scopeByGroup($query, string $group): void
    {
        $query->where('group', $group);
    }

    /**
     * // تصفية حسب المفتاح الكامل group.key
     */
    public function scopeByFullKey($query, string $fullKey): void
    {
        [$group, $key] = explode('.', $fullKey, 2);
        $query->where('group', $group)->where('key', $key);
    }

    /**
     * // الحصول على جميع الإعدادات النشطة (التي لها قيمة)
     */
    public function scopeWithValue($query): void
    {
        $query->whereNotNull('value')->where('value', '!=', '');
    }

    // ================================================================
    //  دوال مساعدة (Helpers)
    // ================================================================

    /**
     * // تحديث أو إنشاء إعداد
     * // Upsert: إذا وجد -> تحديث، إذا لم يوجد -> إنشاء
     */
    public static function upsertSetting(
        string $group,
        string $key,
        mixed $value,
        string $type = 'string',
        ?string $description = null
    ): self {
        $setting = self::firstOrNew([
            'group' => $group,
            'key'   => $key,
        ]);

        $setting->type = $type;
        $setting->setTypedValue($value);

        if ($description) {
            $setting->description = $description;
        }

        $setting->save();

        return $setting;
    }

    /**
     * // الحصول على إعداد مع قيمة افتراضية
     * // هذه دالة static لتستخدم من أي مكان
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        [$group, $settingKey] = explode('.', $key, 2);

        $setting = self::where('group', $group)
            ->where('key', $settingKey)
            ->first();

        if (!$setting) {
            return $default;
        }

        return $setting->getTypedValue();
    }

    // ================================================================
    //  Boot events للتسجيل والتنظيف
    // ================================================================

    protected static function booted(): void
    {
        // // بعد الحفظ، نرسل إشارة لمسح الكاش
        static::saved(function (SystemSetting $setting) {
            \Illuminate\Support\Facades\Cache::forget(
                "system_settings:{$setting->group}.{$setting->key}"
            );
        });

        // // بعد الحذف، نرسل إشارة لمسح الكاش
        static::deleted(function (SystemSetting $setting) {
            \Illuminate\Support\Facades\Cache::forget(
                "system_settings:{$setting->group}.{$setting->key}"
            );
        });
    }
}
```

## استخدام الموديل (Model Usage)

```php
// // أمثلة على استخدام الموديل في التطبيق

// // إنشاء إعداد جديد
SystemSetting::create([
    'group'       => 'general',
    'key'         => 'app_name',
    'value'       => 'Beza',
    'type'        => 'string',
    'description' => 'اسم التطبيق',
]);

// // الحصول على قيمة مع تحويل النوع
$setting = SystemSetting::where('group', 'general')
    ->where('key', 'app_name')
    ->first();
echo $setting->getTypedValue(); // "Beza"

// // استخدام الـ scope
$generalSettings = SystemSetting::byGroup('general')->get();

// // Upsert إعداد
SystemSetting::upsertSetting('fees', 'p2p', 0, 'float', 'نسبة رسوم P2P');

// // تحويل بوليان
$setting = new SystemSetting(['type' => 'boolean', 'value' => '1']);
var_dump($setting->getTypedValue()); // bool(true)
```

## ملاحظات (Notes)

```php
// // 1. لا extends مع موديلات أخرى
// // 2. لا traits خاصة
// // 3. لا علاقات Eloquent
// // 4. التحويل يتم عبر match expression (PHP 8.0+)
// // 5. الأحداث (booted) تمسح الكاش تلقائياً
```
