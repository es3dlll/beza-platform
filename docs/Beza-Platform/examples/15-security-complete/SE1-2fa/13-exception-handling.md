# 13 - استثناءات 2FA (Exception Handling)

## TwoFactorRequiredException

```php
<?php

namespace App\Exceptions;

use Exception;

class TwoFactorRequiredException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'مطلوب رمز المصادقة الثنائية',
            'requires_2fa' => true,
        ], 402);
    }
}
```

## InvalidTwoFactorCodeException

```php
class InvalidTwoFactorCodeException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رمز المصادقة الثنائية غير صحيح',
            'errors' => [
                'two_factor_code' => ['الرمز الذي أدخلته غير صحيح. حاول مرة أخرى.'],
            ],
        ], 422);
    }
}
```

## TwoFactorAlreadyEnabledException

```php
class TwoFactorAlreadyEnabledException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'المصادقة الثنائية مفعلة بالفعل',
        ], 400);
    }
}

class TwoFactorNotEnabledException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'المصادقة الثنائية غير مفعلة',
        ], 400);
    }
}
```

## RecoveryCodeLimitException

```php
class RecoveryCodeLimitException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'انتهت رموز الاسترداد',
            'errors' => [
                'recovery_codes' => ['جميع رموز الاسترداد استنفذت. يرجى تعطيل 2FA وإعادة التفعيل.'],
            ],
        ], 422);
    }
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 402 | TwoFactorRequiredException | مطلوب رمز المصادقة الثنائية |
| 422 | InvalidTwoFactorCodeException | رمز المصادقة الثنائية غير صحيح |
| 400 | TwoFactorAlreadyEnabledException | المصادقة الثنائية مفعلة بالفعل |
| 400 | TwoFactorNotEnabledException | المصادقة الثنائية غير مفعلة |
| 422 | RecoveryCodeLimitException | انتهت رموز الاسترداد |
