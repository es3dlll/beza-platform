# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## فئات الاستثناءات المخصصة

### InvalidPhoneNumberException

```php
<?php
// app/Exceptions/InvalidPhoneNumberException.php

namespace App\Exceptions;

use Exception;

class InvalidPhoneNumberException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'invalidphonenumber' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### OperatorApiException

```php
<?php
// app/Exceptions/OperatorApiException.php

namespace App\Exceptions;

use Exception;

class OperatorApiException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'operatorapi' => [$this->getMessage() ?: 'حدث خطأ'],
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


### TopupLimitExceededException

```php
<?php
// app/Exceptions/TopupLimitExceededException.php

namespace App\Exceptions;

use Exception;

class TopupLimitExceededException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'topuplimitexceeded' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### UnsupportedOperatorException

```php
<?php
// app/Exceptions/UnsupportedOperatorException.php

namespace App\Exceptions;

use Exception;

class UnsupportedOperatorException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'unsupportedoperator' => [$this->getMessage() ?: 'حدث خطأ'],
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
| 422 | InvalidPhoneNumberException | شحن رصيد هاتف |
| 500 | TransactionFailedException | فشلت المعاملة |
| 401 | AuthenticationException | غير مصادق |
| 429 | ThrottleRequestsException | طلبات كثيرة |
