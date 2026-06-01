# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## فئات الاستثناءات المخصصة

### InsufficientBalanceException

```php
<?php
// app/Exceptions/InsufficientBalanceException.php

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct(
        private readonly float $available,
        private readonly float $required,
    ) {
        parent::__construct(
            message: "رصيد غير كافٍ. المتاح: {$available}، المطلوب: {$required}"
        );
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رصيد غير كافٍ',
            'errors'  => [
                'balance' => ["رصيد المحفظة غير كافٍ لإتمام العملية. المتاح: {$this->available}"],
            ],
        ], 422);
    }
}
```

### InvalidPinException

```php
<?php
// app/Exceptions/InvalidPinException.php

namespace App\Exceptions;

use Exception;

class InvalidPinException extends Exception
{
    public function __construct()
    {
        parent::__construct('رمز PIN غير صحيح');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رمز PIN غير صحيح',
            'errors'  => [
                'pin' => ['رمز PIN الذي أدخلته غير صحيح'],
            ],
        ], 422);
    }
}
```

### SelfTransferException

```php
<?php
// app/Exceptions/SelfTransferException.php

namespace App\Exceptions;

use Exception;

class SelfTransferException extends Exception
{
    public function __construct()
    {
        parent::__construct('لا يمكن التحويل إلى نفسك');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن التحويل إلى نفسك',
            'errors'  => [
                'to_phone' => ['لا يمكنك تحويل أموال إلى حسابك الخاص'],
            ],
        ], 422);
    }
}
```

### DailyLimitExceededException

```php
<?php
// app/Exceptions/DailyLimitExceededException.php

namespace App\Exceptions;

use Exception;

class DailyLimitExceededException extends Exception
{
    public function __construct(
        private readonly float $limit,
        private readonly float $currentTotal,
    ) {
        parent::__construct(
            message: "تجاوز الحد اليومي. الحد: {$limit}، المستخدم: {$currentTotal}"
        );
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        $remaining = $this->limit - $this->currentTotal;

        return response()->json([
            'success' => false,
            'message' => 'تجاوز الحد اليومي للتحويل',
            'errors'  => [
                'daily_limit' => [
                    "الحد اليومي للتحويل هو {$this->limit}",
                    "المبلغ المتبقي اليوم: {$remaining}",
                ],
            ],
        ], 422);
    }
}
```

### RecipientNotFoundException

```php
<?php
// app/Exceptions/RecipientNotFoundException.php

namespace App\Exceptions;

use Exception;

class RecipientNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('المستلم غير موجود');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'المستلم غير موجود',
            'errors'  => [
                'to_phone' => ['لا يوجد مستخدم بهذا الرقم في المنصة'],
            ],
        ], 404);
    }
}
```

### WalletNotActiveException

```php
<?php
// app/Exceptions/WalletNotActiveException.php

namespace App\Exceptions;

use Exception;

class WalletNotActiveException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'المحفظة غير نشطة',
            'errors'  => [
                'wallet' => [$this->getMessage() ?: 'محفظة المستخدم غير نشطة حالياً'],
            ],
        ], 422);
    }
}
```

### TransactionFailedException

```php
<?php
// app/Exceptions/TransactionFailedException.php

namespace App\Exceptions;

use Exception;

class TransactionFailedException extends Exception
{
    public function __construct(?string $reason = null)
    {
        parent::__construct($reason ?? 'فشلت المعاملة المالية');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'فشلت المعاملة',
            'errors'  => [
                'transaction' => [$this->getMessage()],
            ],
        ], 500);
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
            // لا حاجة لتسجيل مخصص — Laravel يسجل تلقائياً
        });
    }

    /**
     * تحويل كل الاستثناءات إلى JSON متسق
     */
    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            // استثناءات التحقق (Form Request)
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors'  => $e->errors(),
                ], 422);
            }

            // استثناءات المصادقة
            if ($e instanceof HttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'خطأ في الطلب',
                ], $e->getStatusCode());
            }

            // استثناءات عامة غير متوقعة — 500
            if (!$this->isHttpException($e) && !method_exists($e, 'render')) {
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
| 422 | `InsufficientBalanceException` | رصيد غير كافٍ |
| 422 | `InvalidPinException` | رمز PIN غير صحيح |
| 422 | `SelfTransferException` | لا يمكن التحويل إلى نفسك |
| 422 | `DailyLimitExceededException` | تجاوز الحد اليومي |
| 404 | `RecipientNotFoundException` | المستلم غير موجود |
| 422 | `WalletNotActiveException` | المحفظة غير نشطة |
| 500 | `TransactionFailedException` | فشلت المعاملة |
| 422 | `ValidationException` | بيانات غير صحيحة |
| 401 | `AuthenticationException` | غير مصادق |
| 429 | `ThrottleRequestsException` | طلبات كثيرة |
