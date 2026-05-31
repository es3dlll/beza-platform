# 13 - معالجة الاستثناءات (Exception Handling)

## UserAlreadyExistsException

```php
<?php
// app/Exceptions/UserAlreadyExistsException.php

namespace App\Exceptions;

use Exception;

class UserAlreadyExistsException extends Exception
{
    public function __construct()
    {
        parent::__construct('رقم الهاتف مسجل مسبقاً');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رقم الهاتف مسجل مسبقاً',
            'errors'  => [
                'phone' => ['رقم الهاتف مستخدم بالفعل في منصة Beza'],
            ],
        ], 422);
    }
}
```

## DatabaseTransactionException

```php
<?php
// app/Exceptions/DatabaseTransactionException.php

namespace App\Exceptions;

use Exception;

class DatabaseTransactionException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'فشلت عملية إنشاء الحساب');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'فشلت عملية إنشاء الحساب. يرجى المحاولة لاحقاً',
        ], 500);
    }
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 422 | `UserAlreadyExistsException` | رقم الهاتف مسجل مسبقاً |
| 422 | `ValidationException` | بيانات غير صحيحة |
| 500 | `DatabaseTransactionException` | فشلت عملية إنشاء الحساب |
| 429 | `ThrottleRequestsException` | طلبات كثيرة — حاول لاحقاً |

## تسجيل في Handler

```php
<?php
// app/Exceptions/Handler.php — إضافة

public function register(): void
{
    $this->reportable(function (Throwable $e) {
        if ($e instanceof \App\Exceptions\DatabaseTransactionException) {
            \Illuminate\Support\Facades\Log::error('فشل إنشاء حساب', [
                'error' => $e->getMessage(),
            ]);
        }
    });
}
```
