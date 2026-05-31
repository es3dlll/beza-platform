# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## Form Request

```php
<?php
// app/Http/Requests/BankDepositRequest.php

namespace App\\Http\\Requests;

use Illuminate\FoundationHttpFormRequest;
use Illuminate\ValidationRule;

class BankDepositRequest extends FormRequest
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
                'min:1',
                'max:9999999.99',
            ],
            'currency' => [
                'required',
                'string',
                Rule::in(['SYP', 'USD']),
            ],
            'pin' => [
                'required',
                'string',
                'digits:4',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'المبلغ مطلوب',
            'amount.numeric'  => 'المبلغ يجب أن يكون رقماً',
            'amount.min'      => 'أقل مبلغ هو 1',
            'currency.in'     => 'العملة غير مدعومة',
            'pin.required'    => 'رمز PIN مطلوب',
            'pin.digits'      => 'PIN يجب أن يكون 4 أرقام',
        ];
    }
}
```

## سبب كل قاعدة

| الحقل | القاعدة | السبب |
|-------|---------|-------|
| amount | required | المبلغ مطلوب لإتمام العملية |
| amount | numeric | منع القيم النصية |
| amount | min:1 | لا يمكن إرسال 0 أو أرقام سالبة |
| currency | in:SYP,USD | فقط العملات المدعومة |
| pin | digits:4 | PIN يكون 4 أرقام فقط |
