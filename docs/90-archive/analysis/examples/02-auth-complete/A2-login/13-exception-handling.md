# 13 - معالجة الاستثناءات (Exception Handling)

## InvalidCredentialsException

```php
<?php
// app/Exceptions/InvalidCredentialsException.php

namespace App\Exceptions;

use Exception;

class InvalidCredentialsException extends Exception
{
    public function __construct()
    {
        parent::__construct('بيانات الدخول غير صحيحة');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'بيانات الدخول غير صحيحة',
        ], 401);
    }
}
```

## AccountSuspendedException

```php
<?php
// app/Exceptions/AccountSuspendedException.php

namespace App\Exceptions;

use Exception;

class AccountSuspendedException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'حسابك موقوف');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حسابك موقوف، يرجى التواصل مع الدعم',
        ], 403);
    }
}
```

## AccountLockedException

```php
<?php
// app/Exceptions/AccountLockedException.php

namespace App\Exceptions;

use Exception;

class AccountLockedException extends Exception
{
    public function __construct(
        private readonly int $remainingMinutes = 15,
    ) {
        parent::__construct("تم قفل الحساب. حاول بعد {$remainingMinutes} دقيقة");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "تم قفل الحساب مؤقتاً بسبب كثرة المحاولات الفاشلة. حاول بعد {$this->remainingMinutes} دقيقة",
            'data'    => [
                'locked_remaining_minutes' => $this->remainingMinutes,
            ],
        ], 429);
    }
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 401 | `InvalidCredentialsException` | بيانات الدخول غير صحيحة (رسالة عامة) |
| 403 | `AccountSuspendedException` | حسابك موقوف |
| 429 | `AccountLockedException` | تم قفل الحساب — حاول بعد X دقيقة |
| 422 | `ValidationException` | بيانات غير صحيحة |
| 429 | `ThrottleRequestsException` | طلبات كثيرة |

## معالجة عامة في Handler

```php
<?php
// app/Exceptions/Handler.php

public function register(): void
{
    $this->reportable(function (Throwable $e) {
        if ($e instanceof InvalidCredentialsException) {
            // لا تسجل — متوقع
        }
    });
}
```
