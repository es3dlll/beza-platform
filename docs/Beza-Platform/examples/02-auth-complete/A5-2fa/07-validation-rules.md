# 07 - كل قواعد التحقق + أسبابها — المصادقة الثنائية (2FA)

## Form Request — TwoFactorEnableRequest (لا يحتاج Validation)

لا يوجد validation خاص — فقط المصادقة (auth:api). البيانات يتم توليدها من السيرفر.

## Form Request — VerifyTwoFactorRequest

```php
<?php
// app/Http/Requests/VerifyTwoFactorRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTwoFactorRequest extends FormRequest
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
                'digits:6',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'رمز التحقق مطلوب',
            'code.digits'   => 'الرمز يجب أن يكون 6 أرقام',
        ];
    }
}
```

## Form Request — DisableTwoFactorRequest

```php
<?php
// app/Http/Requests/DisableTwoFactorRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisableTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
            ],
            'code' => [
                'nullable',
                'string',
                'digits:6',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'كلمة المرور مطلوبة لتعطيل 2FA',
            'code.digits'       => 'الرمز يجب أن يكون 6 أرقام',
        ];
    }
}
```

## سبب كل قاعدة

| الحقل | القاعدة | السبب |
|-------|---------|-------|
| `code` | required | لا يمكن التحقق بدون رمز |
| `code` | digits:6 | رمز TOTP دائماً 6 أرقام |
| `password` | required (disable) | يجب إعادة المصادقة لتعطيل 2FA |

## التحقق الإضافي (في Service Layer)

```php
// 1. التحقق من أن 2FA غير مفعل مسبقاً (enable)
if ($user->hasTwoFactorEnabled()) {
    throw new TwoFactorAlreadyEnabledException();
}

// 2. التحقق من صحة رمز TOTP
$google2fa = new \PragmaRX\Google2FA\Google2FA();
$valid = $google2fa->verifyKey($secret, $code, 1); // ±1 window

if (!$valid) {
    throw new InvalidTwoFactorCodeException();
}

// 3. التحقق من كلمة المرور (disable)
if (!Hash::check($request->password, $user->password)) {
    throw new \App\Exceptions\InvalidCredentialsException();
}
```
