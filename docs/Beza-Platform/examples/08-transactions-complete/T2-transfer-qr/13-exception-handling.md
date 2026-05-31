# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## فئات الاستثناءات المخصصة

### InvalidPinException

```php
<?php
// app/Exceptions/InvalidPinException.php

namespace App\Exceptions;

use Exception;

class InvalidPinException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'invalidpin' => [$this->getMessage() ?: 'حدث خطأ'],
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
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'insufficientbalance' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### QRExpiredException

```php
<?php
// app/Exceptions/QRExpiredException.php

namespace App\Exceptions;

use Exception;

class QRExpiredException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'qrexpired' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### QRAlreadyUsedException

```php
<?php
// app/Exceptions/QRAlreadyUsedException.php

namespace App\Exceptions;

use Exception;

class QRAlreadyUsedException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'qralreadyused' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### SelfQRPaymentException

```php
<?php
// app/Exceptions/SelfQRPaymentException.php

namespace App\Exceptions;

use Exception;

class SelfQRPaymentException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'selfqrpayment' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### QRSignatureInvalidException

```php
<?php
// app/Exceptions/QRSignatureInvalidException.php

namespace App\Exceptions;

use Exception;

class QRSignatureInvalidException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'qrsignatureinvalid' => [$this->getMessage() ?: 'حدث خطأ'],
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
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'dailylimitexceeded' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```



## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 422 | InvalidPinException | رمز PIN غير صحيح |
| 422 | InsufficientBalanceException | رصيد غير كافٍ |
| 404 | RecipientNotFoundException | المستلم غير موجود |
| 422 | DailyLimitExceededException | تجاوز الحد اليومي |
| 422 | WalletNotActiveException | المحفظة غير نشطة |
| 422 | InvalidPinException | التحويل عبر QR |
| 500 | TransactionFailedException | فشلت المعاملة |
| 401 | AuthenticationException | غير مصادق |
| 429 | ThrottleRequestsException | طلبات كثيرة |
