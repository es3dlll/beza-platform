# 07 - قواعد التحقق (Validation Rules)

```php
<?php
namespace App\Http\Requests\Card;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueCardRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'card_type' => ['required', Rule::in(['virtual', 'physical'])],
            'currency' => ['required', Rule::in(['SYP', 'USD', 'EUR'])],
            'daily_limit' => ['required', 'numeric', 'min:1000', 'max:50000000'],
            'monthly_limit' => ['required', 'numeric', 'min:10000', 'max:500000000'],
            'card_load' => ['required', 'numeric', 'min:0'],
            'wallet_id' => ['required', 'exists:wallets,id'],
        ];
    }
    public function messages(): array
    {
        return [
            'card_type.in' => 'نوع البطاقة يجب أن يكون virtual أو physical',
            'card_type.required' => 'نوع البطاقة مطلوب',
            'currency.in' => 'العملة يجب أن تكون SYP أو USD أو EUR',
            'currency.required' => 'العملة مطلوبة',
            'daily_limit.min' => 'الحد اليومي يجب أن يكون 1,000 على الأقل',
            'daily_limit.max' => 'الحد اليومي يجب ألا يتجاوز 50,000,000',
            'daily_limit.required' => 'الحد اليومي مطلوب',
            'monthly_limit.min' => 'الحد الشهري يجب أن يكون 10,000 على الأقل',
            'monthly_limit.max' => 'الحد الشهري يجب ألا يتجاوز 500,000,000',
            'card_load.min' => 'رصيد التحميل يجب أن يكون 0 أو أكثر',
            'wallet_id.required' => 'رقم المحفظة مطلوب',
            'wallet_id.exists' => 'المحفظة غير موجودة',
        ];
    }
}
```

## قواعد إضافية في طبقة الخدمة

| القاعدة | الشرح |
|---------|-------|
| ملكية المحفظة | wallet_id يجب أن يعود للمستخدم نفسه |
| رصيد كافٍ | يجب أن يكون رصيد المحفظة >= card_load |
| حد البطاقات للمستخدم | لا يمكن تجاوز 5 بطاقات لكل مستخدم |
| تحقق من Luhn | يتم التحقق من صحة PAN بعد التوليد |
