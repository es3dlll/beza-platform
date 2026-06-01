# 13 - كل الاستثناءات ومعالجتها — المصادقة الثنائية (2FA)

## InvalidTwoFactorCodeException

```php
<?php
// app/Exceptions/InvalidTwoFactorCodeException.php

namespace App\Exceptions;

use Exception;

class InvalidTwoFactorCodeException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'رمز التحقق غير صحيح');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'رمز التحقق غير صحيح',
            'errors'  => [
                'code' => ['رمز التحقق غير صحيح'],
            ],
        ], 422);
    }
}
```

## TwoFactorAlreadyEnabledException

```php
<?php
// app/Exceptions/TwoFactorAlreadyEnabledException.php

namespace App\Exceptions;

use Exception;

class TwoFactorAlreadyEnabledException extends Exception
{
    public function __construct()
    {
        parent::__construct('المصادقة الثنائية مفعلة مسبقاً');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'المصادقة الثنائية مفعلة مسبقاً لحسابك',
            'errors'  => [
                'two_factor' => ['المصادقة الثنائية مفعلة مسبقاً لحسابك'],
            ],
        ], 422);
    }
}
```

## TwoFactorRequiredException

```php
<?php
// app/Exceptions/TwoFactorRequiredException.php

namespace App\Exceptions;

use Exception;

class TwoFactorRequiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('المصادقة الثنائية مطلوبة');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'المصادقة الثنائية مطلوبة لإتمام هذه العملية',
            'errors'  => [
                'two_factor' => ['المصادقة الثنائية مطلوبة لإتمام هذه العملية'],
            ],
            'data'    => [
                'requires_2fa' => true,
            ],
        ], 403);
    }
}
```

## InvalidRecoveryCodeException

```php
<?php
// app/Exceptions/InvalidRecoveryCodeException.php

namespace App\Exceptions;

use Exception;

class InvalidRecoveryCodeException extends Exception
{
    public function __construct()
    {
        parent::__construct('رمز الاسترداد غير صحيح');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رمز الاسترداد غير صحيح أو تم استخدامه مسبقاً',
            'errors'  => [
                'recovery_code' => ['رمز الاسترداد غير صحيح أو تم استخدامه مسبقاً'],
            ],
        ], 422);
    }
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 422 | `InvalidTwoFactorCodeException` | رمز التحقق غير صحيح |
| 422 | `TwoFactorAlreadyEnabledException` | 2FA مفعل مسبقاً |
| 403 | `TwoFactorRequiredException` | المصادقة الثنائية مطلوبة |
| 422 | `InvalidRecoveryCodeException` | رمز الاسترداد غير صحيح |
| 401 | `InvalidCredentialsException` | كلمة المرور غير صحيحة (عند التعطيل) |
