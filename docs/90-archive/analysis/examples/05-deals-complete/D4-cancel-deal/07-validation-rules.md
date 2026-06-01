# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## Form Request — DealCancelRequest

```php
<?php
// app/Http/Requests/DealCancelRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DealCancelRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'سبب الإلغاء مطلوب',
            'reason.min'      => 'يرجى توضيح سبب الإلغاء (10 أحرف على الأقل)',
            'reason.max'      => 'سبب الإلغاء طويل جداً',
        ];
    }
}
```

## التحقق الإضافي

```php
// 1. هل الصفقة قابلة للإلغاء؟
if (!$deal->canBeCancelled()) {
    throw new DealNotCancellableException($deal->status);
}

// 2. هل يوجد مستثمرون لاسترجاع أموالهم؟
$activeCount = $deal->activeInvestorsCount();
if ($activeCount === 0) {
    // تحذير: لا يوجد مستثمرون — إلغاء بسيط
}
```
