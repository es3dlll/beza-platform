# 13 - معالجة الاستثناءات (Exception Handling)

## InvalidOtpException

```php
<?php
// app/Exceptions/InvalidOtpException.php

namespace App\Exceptions;

use Exception;

class InvalidOtpException extends Exception
{
    public function __construct()
    {
        parent::__construct('رمز التحقق غير صحيح');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رمز التحقق غير صحيح',
        ], 422);
    }
}
```

## OtpExpiredException

```php
<?php
// app/Exceptions/OtpExpiredException.php

namespace App\Exceptions;

use Exception;

class OtpExpiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('انتهت صلاحية رمز التحقق');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'انتهت صلاحية رمز التحقق. يرجى طلب رمز جديد',
        ], 422);
    }
}
```

## OtpAttemptsExceededException

```php
<?php
// app/Exceptions/OtpAttemptsExceededException.php

namespace App\Exceptions;

use Exception;

class OtpAttemptsExceededException extends Exception
{
    public function __construct()
    {
        parent::__construct('تجاوزت عدد محاولات التحقق');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'تجاوزت عدد محاولات التحقق المسموح بها. يرجى طلب رمز جديد',
        ], 429);
    }
}
```

## PhoneAlreadyVerifiedException

```php
<?php
// app/Exceptions/PhoneAlreadyVerifiedException.php

namespace App\Exceptions;

use Exception;

class PhoneAlreadyVerifiedException extends Exception
{
    public function __construct()
    {
        parent::__construct('رقم الهاتف موثق مسبقاً');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رقم الهاتف موثق مسبقاً',
        ], 422);
    }
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 422 | `InvalidOtpException` | رمز التحقق غير صحيح |
| 422 | `OtpExpiredException` | انتهت صلاحية الرمز |
| 429 | `OtpAttemptsExceededException` | تجاوزت عدد المحاولات |
| 422 | `PhoneAlreadyVerifiedException` | الهاتف موثق مسبقاً |
| 422 | `ValidationException` | بيانات غير صحيحة |
| 429 | `ThrottleRequestsException` | طلبات كثيرة (3/دقيقة) |
