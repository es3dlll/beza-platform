# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## Form Request — InvestRequest

```php
<?php
// app/Http/Requests/InvestRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvestRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:10',
                'max:9999999.99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'المبلغ مطلوب',
            'amount.numeric'  => 'المبلغ يجب أن يكون رقماً',
            'amount.min'      => 'أقل مبلغ للاستثمار هو 10 USD',
            'amount.max'      => 'المبلغ يتجاوز الحد المسموح',
        ];
    }
}
```

## التحقق الإضافي (في InvestService)

```php
// في InvestService

// 1. هل الصفقة نشطة؟
if (!in_array($deal->status, ['active', 'filled'])) {
    throw new DealNotActiveException();
}

// 2. هل اكتملت الصفقة؟
if ($deal->current_amount >= $deal->target_amount) {
    throw new DealFullyFundedException();
}

// 3. هل المبلغ لا يتجاوز المتبقي؟
if (($deal->current_amount + $amount) > $deal->target_amount) {
    throw new AmountExceedsRemainingException($deal->remaining_amount);
}

// 4. هل المستثمر لديه رصيد كافٍ؟
if ($wallet->available_balance < $amount) {
    throw new InsufficientBalanceException();
}

// 5. هل المستثمر ليس مالك الصفقة؟ (اختياري)
if ($deal->created_by === $user->id) {
    throw new CannotInvestInOwnDealException();
}
```

## ملخص التحقق

| النوع | أين | الترتيب |
|-------|-----|---------|
| صحة المبلغ | Form Request | 1 |
| حالة الصفقة | InvestService | 2 |
| الرصيد | InvestService | 3 |
| Atomic safety | DB::transaction | 4 |
