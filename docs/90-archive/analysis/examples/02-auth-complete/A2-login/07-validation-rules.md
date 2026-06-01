# 07 - قواعد التحقق (Validation Rules)

## Form Request — LoginRequest

```php
<?php
// app/Http/Requests/LoginRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^[0-9+\-\(\)\s]{7,20}$/',
            ],
            'password' => [
                'required',
                'string',
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
            'phone.required'   => 'رقم الهاتف مطلوب',
            'phone.regex'      => 'صيغة رقم الهاتف غير صحيحة',
            'password.required' => 'كلمة المرور مطلوبة',
        ];
    }
}
```

## سبب كل قاعدة

| الحقل | القاعدة | السبب |
|-------|---------|-------|
| `phone` | required | لا يمكن تسجيل الدخول بدون رقم هاتف |
| `phone` | regex | منع Injection عبر أرقام مشوهة |
| `password` | required | لا يمكن تسجيل الدخول بدون كلمة مرور |
| `device_id` | nullable | اختياري — لتحديد الجهاز وتحديثه |

## التحقق الإضافي (في Service Layer)

```php
// 1. التحقق من وجود المستخدم
$user = User::where('phone', $request->phone)->first();
if (!$user) {
    throw new InvalidCredentialsException();
}

// 2. التحقق من كلمة المرور
if (!Hash::check($request->password, $user->password)) {
    throw new InvalidCredentialsException();
}

// 3. التحقق من أن الحساب غير suspended
if ($user->isSuspended()) {
    throw new AccountSuspendedException();
}

// 4. التحقق من القفل (5 محاولات فاشلة)
if ($this->isAccountLocked($user)) {
    throw new AccountLockedException();
}
```

## مقارنة أنواع التحقق

| النوع | أين يتم | الترتيب |
|-------|---------|---------|
| Structural validation | Form Request | 1 |
| Account existence | Service Layer | 2 |
| Password verification | Service Layer | 3 |
| Account status | Service Layer | 4 |
| Rate limiting | Redis + Service | 5 |
