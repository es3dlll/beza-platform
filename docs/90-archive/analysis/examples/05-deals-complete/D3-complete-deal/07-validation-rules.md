# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## Form Request — DealCompleteRequest

```php
<?php
// app/Http/Requests/DealCompleteRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DealCompleteRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return $this->user()?->is_admin ?? false;
    }

    public function rules(): array
    {
        return [
            'profit_actual' => [
                'required',
                'numeric',
                'min:0',
                'max:1000', // 1000% كحد أقصى للطوارئ
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'profit_actual.required' => 'نسبة الربح الفعلية مطلوبة',
            'profit_actual.numeric'  => 'نسبة الربح يجب أن تكون رقماً',
            'profit_actual.min'      => 'نسبة الربح الفعلية لا يمكن أن تكون سالبة',
            'profit_actual.max'      => 'نسبة الربح الفعلية تتجاوز الحد الأقصى',
        ];
    }
}
```

## التحقق الإضافي

```php
// 1. هل حالة الصفقة تسمح بالإتمام؟
if (!$deal->canBeCompleted()) {
    throw new DealNotCompletableException($deal->status);
}

// 2. هل يوجد مستثمرون نشطون؟
$activeInvestments = $deal->investments()->where('status', 'active')->count();
if ($activeInvestments === 0) {
    throw new NoActiveInvestorsException();
}
```
