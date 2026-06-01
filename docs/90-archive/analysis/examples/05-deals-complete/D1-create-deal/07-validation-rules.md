# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## Form Request — DealStoreRequest

```php
<?php
// app/Http/Requests/DealStoreRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DealStoreRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_amount' => ['required', 'numeric', 'min:100', 'max:999999999.99'],
            'currency' => ['required', Rule::in(['SYP', 'USD'])],
            'expected_profit_percentage' => ['required', 'numeric', 'min:1', 'max:100'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'category' => ['required', 'string', 'max:100'],
            'risk_level' => ['required', Rule::in(['low', 'medium', 'high'])],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'عنوان الصفقة مطلوب',
            'target_amount.required' => 'رأس المال المستهدف مطلوب',
            'target_amount.min'    => 'أقل رأس مال للصفقة هو 100 USD',
            'currency.in'          => 'العملة غير مدعومة (SYP أو USD فقط)',
            'expected_profit_percentage.required' => 'نسبة الربح المتوقعة مطلوبة',
            'expected_profit_percentage.min' => 'نسبة الربح يجب أن تكون 1% على الأقل',
            'expected_profit_percentage.max' => 'نسبة الربح يجب أن تكون 100% كحد أقصى',
            'duration_days.min'    => 'مدة الصفقة يوم واحد على الأقل',
            'duration_days.max'    => 'مدة الصفقة 10 سنوات كحد أقصى',
            'risk_level.in'        => 'مستوى المخاطرة يجب أن يكون low, medium, أو high',
        ];
    }
}
```

## سبب كل قاعدة

| الحقل | القاعدة | السبب |
|-------|---------|-------|
| title | required, max:255 | عنوان واضح للصفقة |
| target_amount | min:100 | ضمان جدية الصفقة (أقل من 100 USD لا يغطي التكاليف) |
| expected_profit_percentage | min:1, max:100 | منطقياً: 0% ليس استثماراً، فوق 100% غير واقعي |
| duration_days | min:1, max:3650 | مدة معقولة (يوم - 10 سنوات) |
| risk_level | in:low,medium,high | تصنيف المخاطرة للمستثمرين |
