# 07 - قواعد التحقق (Validation Rules)

## Form Request — RegisterRequest

```php
<?php
// app/Http/Requests/RegisterRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{8}$/',
                Rule::unique('users', 'phone'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'pin_code' => [
                'required',
                'string',
                'digits:4',
                'confirmed',
            ],
            'device_id' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'الاسم مطلوب',
            'name.max'              => 'الاسم طويل جداً (حد أقصى 255 حرف)',
            'phone.required'        => 'رقم الهاتف مطلوب',
            'phone.regex'           => 'رقم الهاتف يجب أن يبدأ بـ 09 ويتكون من 10 أرقام',
            'phone.unique'          => 'رقم الهاتف مسجل مسبقاً',
            'password.required'     => 'كلمة المرور مطلوبة',
            'password.min'          => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed'    => 'تأكيد كلمة المرور غير متطابق',
            'pin_code.required'     => 'رمز PIN مطلوب',
            'pin_code.digits'       => 'PIN يجب أن يكون 4 أرقام',
            'pin_code.confirmed'    => 'تأكيد PIN غير متطابق',
        ];
    }
}
```

## سبب كل قاعدة

| الحقل | القاعدة | السبب |
|-------|---------|-------|
| `name` | required | لا يمكن إنشاء حساب بدون اسم |
| `name` | max:255 | حد أقصى لحجم التخزين في DB |
| `phone` | regex:/^09[0-9]{8}$/ | أرقام الهواتف السورية فقط (10 أرقام، تبدأ بـ 09) |
| `phone` | unique:users | لا يمكن أن يكون رقم هاتف مكرر |
| `password` | min:8 | متطلبات أمان أساسية |
| `password` | confirmed | تأكيد كلمة المرور لمنع الأخطاء الإملائية |
| `pin_code` | digits:4 | PIN يكون 4 أرقام فقط |
| `pin_code` | confirmed | تأكيد PIN لمنع الأخطاء |
