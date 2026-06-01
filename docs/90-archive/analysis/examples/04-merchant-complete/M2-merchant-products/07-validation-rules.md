# 07 - قواعد التحقق (Validation Rules)

```php
<?php
namespace App\Http\Requests\Merchant;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_syp' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'price_usd' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'category'  => ['nullable', 'string', 'max:100'],
            'stock'     => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['boolean'],
            'images'    => ['nullable', 'array', 'max:5'],
            'images.*'  => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
    public function messages(): array {
        return [
            'name.required' => 'اسم المنتج مطلوب',
            'price_syp.required' => 'سعر المنتج بالليرة مطلوب',
            'price_usd.required' => 'سعر المنتج بالدولار مطلوب',
            'images.*.max'  => 'حجم الصورة لا يتجاوز 2MB',
            'images.max'    => 'الحد الأقصى 5 صور',
        ];
    }
}
```
