# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## فئات الاستثناءات المخصصة

### PaymentGatewayException

```php
<?php
// app/Exceptions/PaymentGatewayException.php

namespace App\Exceptions;

use Exception;

class PaymentGatewayException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'paymentgateway' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### CardDeclinedException

```php
<?php
// app/Exceptions/CardDeclinedException.php

namespace App\Exceptions;

use Exception;

class CardDeclinedException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'carddeclined' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### ThreeDSFailedException

```php
<?php
// app/Exceptions/ThreeDSFailedException.php

namespace App\Exceptions;

use Exception;

class ThreeDSFailedException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'threedsfailed' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### CardDepositLimitExceededException

```php
<?php
// app/Exceptions/CardDepositLimitExceededException.php

namespace App\Exceptions;

use Exception;

class CardDepositLimitExceededException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'carddepositlimitexceeded' => [$this->getMessage() ?: 'حدث خطأ'],
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
| 422 | PaymentGatewayException | إيداع بطاقة ائتمانية |
| 500 | TransactionFailedException | فشلت المعاملة |
| 401 | AuthenticationException | غير مصادق |
| 429 | ThrottleRequestsException | طلبات كثيرة |
