# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

_(أنظر D1-create-deal/13-exception-handling.md للاستثناءات العامة)_

## استثناءات خاصة بالاستثمار

```php
<?php
// app/Exceptions/AmountExceedsRemainingException.php

namespace App\Exceptions;

use Exception;

class AmountExceedsRemainingException extends Exception
{
    public function __construct(
        private readonly float $remaining
    ) {
        parent::__construct("المبلغ يتجاوز المتبقي. المتبقي: {$remaining}");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'المبلغ يتجاوز المبلغ المتبقي للصفقة',
            'errors'  => [
                'amount' => ["المبلغ المتبقي للاستثمار في هذه الصفقة هو {$this->remaining}"],
            ],
        ], 422);
    }
}
```

```php
<?php
// app/Exceptions/AlreadyReferredException.php

class AlreadyReferredException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لقد تمت دعوتك بالفعل',
        ], 422);
    }
}
```

## جدول رموز الأخطاء الخاصة بالاستثمار

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 422 | AmountExceedsRemainingException | المبلغ يتجاوز المتبقي |
| 422 | DealNotActiveException | الصفقة غير نشطة |
| 422 | DealFullyFundedException | الصفقة ممولة بالكامل |
| 422 | CannotInvestInOwnDealException | لا يمكن الاستثمار في صفقتك |
| 422 | InsufficientBalanceException | رصيد غير كافٍ |
