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


### MinWithdrawNotMetException

```php
<?php
// app/Exceptions/MinWithdrawNotMetException.php

namespace App\Exceptions;

use Exception;

class MinWithdrawNotMetException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'minwithdrawnotmet' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### MaxDailyWithdrawExceededException

```php
<?php
// app/Exceptions/MaxDailyWithdrawExceededException.php

namespace App\Exceptions;

use Exception;

class MaxDailyWithdrawExceededException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'maxdailywithdrawexceeded' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### BankAccountNotVerifiedException

```php
<?php
// app/Exceptions/BankAccountNotVerifiedException.php

namespace App\Exceptions;

use Exception;

class BankAccountNotVerifiedException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'bankaccountnotverified' => [$this->getMessage() ?: 'حدث خطأ'],
            ],
        ], 422);
    }
}
```


### WithdrawProcessingException

```php
<?php
// app/Exceptions/WithdrawProcessingException.php

namespace App\Exceptions;

use Exception;

class WithdrawProcessingException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage() ?: 'حدث خطأ',
            'errors'  => [
                'withdrawprocessing' => [$this->getMessage() ?: 'حدث خطأ'],
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
| 422 | InsufficientBalanceException | سحب بنكي |
| 500 | TransactionFailedException | فشلت المعاملة |
| 401 | AuthenticationException | غير مصادق |
| 429 | ThrottleRequestsException | طلبات كثيرة |
