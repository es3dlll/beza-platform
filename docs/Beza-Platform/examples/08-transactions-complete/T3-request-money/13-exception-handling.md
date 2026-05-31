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


### RequestExpiredException

```php
<?php
// app/Exceptions/RequestExpiredException.php

namespace App\Exceptions;

use Exception;

class RequestExpiredException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'requestexpired' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### SelfRequestException

```php
<?php
// app/Exceptions/SelfRequestException.php

namespace App\Exceptions;

use Exception;

class SelfRequestException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'selfrequest' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### RequestNotFoundException

```php
<?php
// app/Exceptions/RequestNotFoundException.php

namespace App\Exceptions;

use Exception;

class RequestNotFoundException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'requestnotfound' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### RequestAlreadyRespondedException

```php
<?php
// app/Exceptions/RequestAlreadyRespondedException.php

namespace App\Exceptions;

use Exception;

class RequestAlreadyRespondedException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'requestalreadyresponded' => [$this->getMessage() ?: 'حدث خطأ'],
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
| 422 | InvalidPinException | طلب المال |
| 500 | TransactionFailedException | فشلت المعاملة |
| 401 | AuthenticationException | غير مصادق |
| 429 | ThrottleRequestsException | طلبات كثيرة |
