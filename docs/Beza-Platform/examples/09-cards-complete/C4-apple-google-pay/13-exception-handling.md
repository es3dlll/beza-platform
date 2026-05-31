# 13 - معالجة الاستثناءات (Exception Handling)

## Custom Exceptions

```php
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class WalletEnrollmentFailedException extends Exception
{
    public function __construct(
        string $message = 'فشل الاشتراك في المحفظة الرقمية',
        public readonly ?string $networkError = null
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'enrollment' => [$this->getMessage()],
            ],
            'network_error' => $this->networkError,
        ], 422);
    }
}

class DeviceNotSupportedException extends Exception
{
    public function __construct(string $message = 'الجهاز غير مدعوم للمحافظ الرقمية')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'device_id' => ['الجهاز لا يدعم Apple Pay أو Google Pay'],
            ],
        ], 400);
    }
}

class TokenExpiredException extends Exception
{
    public function __construct(string $message = 'رمز المحفظة الرقمية منتهي الصلاحية')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data' => [
                'requires_renewal' => true,
            ],
        ], 401);
    }
}

class WalletAlreadyEnrolledException extends Exception
{
    public function __construct(string $message = 'البطاقة مشتركة بالفعل في المحفظة الرقمية على هذا الجهاز')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 409);
    }
}
```

## Exception Handler Registration

```php
<?php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->reportable(function (WalletEnrollmentFailedException $e) {
            Log::error('Wallet enrollment failed', [
                'network_error' => $e->networkError,
            ]);
        });
    }

    public function render($request, \Throwable $e)
    {
        if ($request->expectsJson() && method_exists($e, 'render')) {
            return $e->render();
        }

        return parent::render($request, $e);
    }
}
```

## Error Codes Summary

| HTTP | Exception | Arabic Message |
|------|-----------|----------------|
| 400 | DeviceNotSupportedException | الجهاز غير مدعوم للمحافظ الرقمية |
| 401 | TokenExpiredException | رمز المحفظة الرقمية منتهي الصلاحية |
| 409 | WalletAlreadyEnrolledException | البطاقة مشتركة بالفعل على هذا الجهاز |
| 422 | WalletEnrollmentFailedException | فشل الاشتراك في المحفظة الرقمية |
| 422 | InvalidWalletTypeException | نوع المحفظة الرقمية غير صالح |
