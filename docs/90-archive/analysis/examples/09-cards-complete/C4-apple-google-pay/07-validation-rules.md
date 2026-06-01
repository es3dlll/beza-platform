# 07 - قواعد التحقق (Validation Rules)

## FormRequest: WalletEnrollRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletEnrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('card'));
    }

    public function rules(): array
    {
        return [
            'wallet_type' => [
                'required',
                'string',
                Rule::in(['apple_pay', 'google_pay']),
            ],
            'device_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/',
            ],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_public_key' => ['required', 'string'],
            'push_token' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'wallet_type.required' => 'نوع المحفظة الرقمية مطلوب',
            'wallet_type.in' => 'نوع المحفظة يجب أن يكون Apple Pay أو Google Pay',
            'device_id.required' => 'معرف الجهاز مطلوب',
            'device_id.regex' => 'صيغة معرف الجهاز غير صالحة',
            'device_public_key.required' => 'المفتاح العام للجهاز مطلوب',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $card = $this->route('card');

            if ($card && $card->status !== 'active') {
                $validator->errors()->add('card_id', 'البطاقة غير نشطة ولا يمكن إضافتها إلى المحفظة الرقمية');
            }

            if ($card && !in_array($card->network, ['visa', 'mastercard', 'mada'])) {
                $validator->errors()->add('card_id', 'شبكة البطاقة غير مدعومة للمحافظ الرقمية');
            }

            $existingEnrollments = \App\Models\WalletEnrollment::where('card_id', $card?->id)
                ->where('status', 'active')
                ->count();

            if ($card && $existingEnrollments >= 5) {
                $validator->errors()->add('card_id', 'تم الوصول إلى الحد الأقصى لعدد الاشتراكات النشطة (5)');
            }
        });
    }
}
```

## Validation Rules Summary

| Field | Rule | Description |
|-------|------|-------------|
| wallet_type | required, in:[apple_pay,google_pay] | Digital wallet provider |
| device_id | required, alphanumeric+hyphen | Unique device identifier |
| device_name | nullable, max:255 | Human-readable device name |
| device_public_key | required, string | RSA public key from device |
| push_token | nullable, string | FCM/APNS push notification token |

## Business Rule Validation

| Check | Condition | Error Message |
|-------|-----------|---------------|
| Card active | status === 'active' | البطاقة غير نشطة |
| Network support | visa/mastercard/mada | شبكة البطاقة غير مدعومة |
| Max enrollments | < 5 per card | الحد الأقصى 5 اشتراكات |
| Duplicate device | unique(card_id, wallet_type, device_id) | الجهاز مشترك بالفعل |
