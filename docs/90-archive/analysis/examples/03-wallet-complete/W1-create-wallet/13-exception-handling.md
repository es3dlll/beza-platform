# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## فئات الاستثناءات المخصصة

### UserNotActiveException

```php
<?php
// app/Exceptions/UserNotActiveException.php

namespace App\Exceptions;

use Exception;

class UserNotActiveException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'المستخدم غير نشط');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors'  => [
                'user' => ['لا يمكن إنشاء محفظة لمستخدم غير نشط'],
            ],
        ], 422);
    }
}
```

### WalletsAlreadyExistException

```php
<?php
// app/Exceptions/WalletsAlreadyExistException.php

namespace App\Exceptions;

use Exception;

class WalletsAlreadyExistException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'المستخدم لديه محافظ مسبقاً');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors'  => [
                'wallet' => ['هذا المستخدم لديه محافظ بالفعل'],
            ],
        ], 422);
    }
}
```

### WalletCreationFailedException

```php
<?php
// app/Exceptions/WalletCreationFailedException.php

namespace App\Exceptions;

use Exception;

class WalletCreationFailedException extends Exception
{
    public function __construct(?string $reason = null)
    {
        parent::__construct($reason ?? 'فشل إنشاء المحفظة');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'فشل إنشاء المحفظة',
            'errors'  => [
                'wallet' => [$this->getMessage()],
            ],
        ], 500);
    }
}
```

### InvalidWalletNumberException

```php
<?php
// app/Exceptions/InvalidWalletNumberException.php

namespace App\Exceptions;

use Exception;

class InvalidWalletNumberException extends Exception
{
    public function __construct()
    {
        parent::__construct('رقم المحفظة غير صحيح');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رقم المحفظة غير صحيح',
            'errors'  => [
                'wallet_number' => ['رقم المحفظة المطلوب غير موجود'],
            ],
        ], 404);
    }
}
```

## تسجيل الاستثناءات في Handler

```php
<?php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password', 'password', 'password_confirmation', 'pin',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors'  => $e->errors(),
                ], 422);
            }

            if ($e instanceof HttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'خطأ في الطلب',
                ], $e->getStatusCode());
            }

            if (!method_exists($e, 'render')) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ داخلي',
                ], 500);
            }
        }

        return parent::render($request, $e);
    }
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 422 | `UserNotActiveException` | المستخدم غير نشط |
| 422 | `WalletsAlreadyExistException` | المستخدم لديه محافظ مسبقاً |
| 500 | `WalletCreationFailedException` | فشل إنشاء المحفظة |
| 404 | `InvalidWalletNumberException` | رقم المحفظة غير صحيح |
| 422 | `ValidationException` | بيانات غير صحيحة |
| 401 | `AuthenticationException` | غير مصادق |
