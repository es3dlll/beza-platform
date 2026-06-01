# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## فئات الاستثناءات المخصصة

### WalletsNotFoundException

```php
<?php
// app/Exceptions/WalletsNotFoundException.php

namespace App\Exceptions;

use Exception;

class WalletsNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('لم يتم العثور على محافظ');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors'  => [
                'wallets' => ['المستخدم الحالي ليس لديه محافظ'],
            ],
        ], 404);
    }
}
```

### WalletNotActiveException (للمحفظة غير النشطة)

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

### CacheCorruptedException

```php
<?php
// app/Exceptions/CacheCorruptedException.php

namespace App\Exceptions;

use Exception;

class CacheCorruptedException extends Exception
{
    public function __construct()
    {
        parent::__construct('بيانات المخبأ غير صحيحة');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'خطأ في قراءة الرصيد',
        ], 500);
    }
}
```

## تسجيل الاستثناءات في Handler

```php
<?php
// app/Exceptions/Handler.php — إضافة معالجة

use App\Exceptions\WalletsNotFoundException;
use App\Exceptions\WalletNotActiveException;

public function render($request, Throwable $e)
{
    if ($request->expectsJson() || $request->is('api/*')) {
        if ($e instanceof WalletsNotFoundException) {
            return $e->render();
        }

        if ($e instanceof WalletNotActiveException) {
            return $e->render();
        }

        // ... باقي المعالجات
    }

    return parent::render($request, $e);
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 404 | `WalletsNotFoundException` | لم يتم العثور على محافظ |
| 422 | `WalletNotActiveException` | المحفظة غير نشطة |
| 500 | `CacheCorruptedException` | خطأ في قراءة الرصيد |
| 401 | `AuthenticationException` | غير مصادق |
| 429 | `ThrottleRequestsException` | طلبات كثيرة |
