# 07 - قواعد التحقق (Validation Rules)

## Form Request — RequestOtpRequest

```php
<?php
// app/Http/Requests/RequestOtpRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
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
                'exists:users,phone',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex'    => 'صيغة رقم الهاتف غير صحيحة',
            'phone.exists'   => 'رقم الهاتف غير مسجل في المنصة',
        ];
    }
}
```

## Form Request — VerifyOtpRequest

```php
<?php
// app/Http/Requests/VerifyOtpRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
                'exists:users,phone',
            ],
            'otp' => [
                'required',
                'string',
                'digits:6',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex'    => 'صيغة رقم الهاتف غير صحيحة',
            'phone.exists'   => 'رقم الهاتف غير مسجل',
            'otp.required'   => 'رمز OTP مطلوب',
            'otp.digits'     => 'OTP يجب أن يكون 6 أرقام',
        ];
    }
}
```

## سبب كل قاعدة

| الحقل | القاعدة | السبب |
|-------|---------|-------|
| `phone` | required | لا يمكن إرسال OTP بدون رقم |
| `phone` | exists | الرقم يجب أن يكون مسجلاً مسبقاً |
| `otp` | required | لا يمكن التحقق بدون رمز |
| `otp` | digits:6 | OTP دائماً 6 أرقام |

## التحقق الإضافي (في Service Layer)

```php
// 1. التحقق من وجود OTP في Cache
$cached = Cache::get('otp_' . $phone);
if (!$cached) {
    throw new OtpExpiredException();
}

// 2. التحقق من عدم انتهاء الصلاحية
if ($cached['expires_at'] < now()->timestamp) {
    Cache::forget('otp_' . $phone);
    throw new OtpExpiredException();
}

// 3. التحقق من عدد المحاولات
if ($cached['attempts'] >= 5) {
    Cache::forget('otp_' . $phone);
    throw new OtpAttemptsExceededException();
}

// 4. مقارنة الرمز
if ($cached['code'] !== $request->otp) {
    // زيادة عدد المحاولات
    $cached['attempts']++;
    Cache::put('otp_' . $phone, $cached, now()->addMinutes(5));
    throw new InvalidOtpException();
}
```
