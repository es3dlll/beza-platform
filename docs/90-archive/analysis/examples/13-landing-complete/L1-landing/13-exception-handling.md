# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## فئات الاستثناءات المخصصة

### DuplicateSubscriptionException

```php
<?php
// app/Exceptions/Landing/DuplicateSubscriptionException.php

namespace App\Exceptions\Landing;

use Exception;

class DuplicateSubscriptionException extends Exception
{
    public function __construct()
    {
        parent::__construct('هذا البريد مسجل بالفعل في النشرة البريدية');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors'  => [
                'email' => ['هذا البريد الإلكتروني مشترك بالفعل'],
            ],
        ], 422);
    }
}
```

### InvalidSourceException

```php
<?php
// app/Exceptions/Landing/InvalidSourceException.php

namespace App\Exceptions\Landing;

use Exception;

class InvalidSourceException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'مصدر غير صحيح',
        ], 422);
    }
}
```

## معالجة الاستثناءات في Handler

```php
<?php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use App\Exceptions\Landing\DuplicateSubscriptionException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors'  => $e->errors(),
                ], 422);
            }

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
| 422 | `DuplicateSubscriptionException` | هذا البريد مسجل بالفعل |
| 422 | `InvalidSourceException` | مصدر غير صحيح |
| 422 | `ValidationException` | بيانات غير صحيحة |
| 500 | `Exception` عام | حدث خطأ داخلي |
