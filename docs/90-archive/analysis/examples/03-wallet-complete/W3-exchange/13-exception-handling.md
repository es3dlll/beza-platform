# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## فئات الاستثناءات المخصصة

### SameCurrencyExchangeException

```php
<?php
// app/Exceptions/SameCurrencyExchangeException.php

namespace App\Exceptions;

use Exception;

class SameCurrencyExchangeException extends Exception
{
    public function __construct()
    {
        parent::__construct('لا يمكن الصرافة لنفس العملة');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن الصرافة لنفس العملة',
            'errors'  => [
                'currencies' => ['يجب أن تختلف عملة المصدر عن عملة الوجهة'],
            ],
        ], 422);
    }
}
```

### MinimumAmountException

```php
<?php
// app/Exceptions/MinimumAmountException.php

namespace App\Exceptions;

use Exception;

class MinimumAmountException extends Exception
{
    public function __construct(
        private readonly float $minAmount,
        private readonly string $currency,
    ) {
        parent::__construct("الحد الأدنى للصرافة هو {$minAmount} {$currency}");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "الحد الأدنى للصرافة هو {$this->minAmount} {$this->currency}",
            'errors'  => [
                'amount' => ["الحد الأدنى: {$this->minAmount} {$this->currency}"],
            ],
        ], 422);
    }
}
```

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
        parent::__construct("رصيد غير كافٍ. المتاح: {$available}، المطلوب: {$required}");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رصيد غير كافٍ',
            'errors'  => [
                'balance' => ["رصيد المحفظة غير كافٍ لإتمام عملية الصرافة. المتاح: {$this->available}، المطلوب: {$this->required}"],
            ],
        ], 422);
    }
}
```

### RateNotFoundException

```php
<?php
// app/Exceptions/RateNotFoundException.php

namespace App\Exceptions;

use Exception;

class RateNotFoundException extends Exception
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("لا يوجد سعر صرف متاح: {$from} → {$to}");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'سعر الصرف غير متاح حالياً',
            'errors'  => [
                'rate' => ['يرجى المحاولة لاحقاً'],
            ],
        ], 503);
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

## تسجيل الاستثناءات في Handler

```php
<?php
// app/Exceptions/Handler.php

use App\Exceptions\SameCurrencyExchangeException;
use App\Exceptions\MinimumAmountException;
use App\Exceptions\RateNotFoundException;

public function render($request, Throwable $e)
{
    if ($request->expectsJson() || $request->is('api/*')) {
        if ($e instanceof SameCurrencyExchangeException) return $e->render();
        if ($e instanceof MinimumAmountException) return $e->render();
        if ($e instanceof RateNotFoundException) return $e->render();
        // ... باقي المعالجات
    }
    return parent::render($request, $e);
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 422 | `SameCurrencyExchangeException` | لا يمكن الصرافة لنفس العملة |
| 422 | `MinimumAmountException` | الحد الأدنى للصرافة هو X |
| 422 | `InsufficientBalanceException` | رصيد غير كافٍ |
| 503 | `RateNotFoundException` | سعر الصرف غير متاح |
| 422 | `WalletNotActiveException` | المحفظة غير نشطة |
| 422 | `ValidationException` | بيانات غير صحيحة |
