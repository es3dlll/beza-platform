# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## Form Request — ReferralClaimRequest

```php
<?php
// app/Http/Requests/ReferralClaimRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReferralClaimRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'size:8',
                'exists:referral_codes,code',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'كود الإحالة مطلوب',
            'code.size'     => 'كود الإحالة يجب أن يكون 8 أحرف',
            'code.exists'   => 'كود الإحالة غير صحيح',
        ];
    }
}
```

## التحقق الإضافي (في ReferralService)

```php
// 1. كود الإحالة نشط؟
$code = ReferralCode::where('code', $request->code)
    ->where('is_active', true)
    ->firstOrFail();

// 2. المستخدم ليس المدعو بنفسه
if ($code->user_id === $authUser->id) {
    throw new SelfReferralException();
}

// 3. المستخدم المدعو جديد (لم يسجل من قبل)
if ($authUser->referred_by !== null) {
    throw new AlreadyReferredException();
}

// 4. المستخدم المدعو ليس لديه دعوة سابقة من هذا الداعي
$existingReward = ReferralReward::where('referrer_id', $code->user_id)
    ->where('referred_id', $authUser->id)
    ->exists();
if ($existingReward) {
    throw new DuplicateReferralException();
}
```

## إنشاء كود الإحالة

```php
// POST /api/v1/referral/code — لا يحتاج validation خاص
// فقط يتأكد من أن المستخدم مصادق عليه
```
