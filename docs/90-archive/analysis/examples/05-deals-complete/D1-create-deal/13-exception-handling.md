# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

```php
<?php
// app/Exceptions/DealNotActiveException.php

namespace App\Exceptions;

use Exception;

class DealNotActiveException extends Exception
{
    public function __construct()
    {
        parent::__construct('الصفقة غير نشطة');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'الصفقة غير نشطة',
            'errors'  => [
                'deal' => ['هذه الصفقة غير متاحة حالياً'],
            ],
        ], 422);
    }
}
```

```php
<?php
// app/Exceptions/DealFullyFundedException.php

class DealFullyFundedException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'اكتمل رأس مال الصفقة',
            'errors'  => ['deal' => ['هذه الصفقة اكتمل تمويلها']],
        ], 422);
    }
}
```

```php
<?php
// app/Exceptions/CannotInvestInOwnDealException.php

class CannotInvestInOwnDealException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن الاستثمار في صفقة أنشأتها',
        ], 422);
    }
}
```

## جدول رموز الأخطاء

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 422 | DealNotActiveException | الصفقة غير نشطة |
| 422 | DealFullyFundedException | اكتمل رأس مال الصفقة |
| 422 | CannotInvestInOwnDealException | لا يمكن الاستثمار في صفقتك |
| 422 | AmountExceedsRemainingException | المبلغ يتجاوز المتبقي |
| 422 | DealNotCompletableException | الصفقة غير قابلة للإكمال |
| 422 | DealNotCancellableException | الصفقة غير قابلة للإلغاء |
| 422 | NoActiveInvestorsException | لا يوجد مستثمرون نشطون |
