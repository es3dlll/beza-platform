# 13 - الاستثناءات المخصصة (Custom Exceptions)

## قائمة الاستثناءات

| الاستثناء | رمز HTTP | الرسالة | يُرمى من |
|-----------|---------|---------|---------|
| InsufficientBalanceException | 422 | رصيد غير كافٍ | WalletService |
| InsufficientHoldingException | 422 | رصيد جرامات غير كافٍ | CommodityService |
| PriceExpiredException | 422 | انتهت صلاحية السعر (أكثر من 30 ثانية) | CommodityService |
| MarketClosedException | 503 | السوق مغلق (عطلة نهاية الأسبوع) | CommodityService |
| MinimumHoldingPeriodException | 422 | لم تمض 24 ساعة على الشراء | CommodityService |
| SpreadTooHighException | 502 | هامش السبريد مرتفع جداً | PriceFeedProvider |
| WalletNotActiveException | 422 | المحفظة غير نشطة | WalletService |

## InsufficientBalanceException

```php
<?php
// app/Exceptions/InsufficientBalanceException.php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientBalanceException extends Exception
{
    public function __construct(
        public readonly float $available,
        public readonly float $required,
    ) {
        parent::__construct(
            message: 'رصيد غير كافٍ. الرصيد المتاح: ' . $available . '، المطلوب: ' . $required
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رصيد غير كافٍ',
            'errors'  => [
                'balance' => [
                    'رصيد المحفظة غير كافٍ لإتمام العملية. المتاح: ' .
                    number_format($this->available, 2) .
                    '، المطلوب: ' . number_format($this->required, 2),
                ],
            ],
        ], 422);
    }
}
```

## InsufficientHoldingException

```php
<?php
// app/Exceptions/InsufficientHoldingException.php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientHoldingException extends Exception
{
    public function __construct(
        public readonly float $available,
        public readonly float $requested,
    ) {
        parent::__construct(
            message: 'رصيد جرامات غير كافٍ. المتاح: ' . $available . '، المطلوب: ' . $requested
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'رصيد جرامات غير كافٍ',
            'errors'  => [
                'holding' => [
                    'رصيدك من الجرامات غير كافٍ. المتاح: ' .
                    number_format($this->available, 4) .
                    ' جم، المطلوب: ' . number_format($this->requested, 4) . ' جم',
                ],
            ],
        ], 422);
    }
}
```

## PriceExpiredException

```php
<?php
// app/Exceptions/PriceExpiredException.php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PriceExpiredException extends Exception
{
    public function __construct(
        public readonly int $secondsElapsed,
    ) {
        parent::__construct(
            message: 'انتهت صلاحية السعر. مضي ' . $secondsElapsed . ' ثانية (الحد الأقصى 30 ثانية)'
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'انتهت صلاحية السعر',
            'errors'  => [
                'price' => [
                    'لقد تغير السعر منذ آخر تحديث. يرجى تحديث السعر والمحاولة مرة أخرى.',
                ],
            ],
        ], 422);
    }
}
```

## MarketClosedException

```php
<?php
// app/Exceptions/MarketClosedException.php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class MarketClosedException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            message: 'السوق مغلق حالياً. سوق الذهب والفضة العالمي مفتوح من الأحد 23:00 حتى الجمعة 22:00 (GMT).'
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'السوق مغلق حالياً',
            'errors'  => [
                'market' => [
                    'سوق الذهب والفضة العالمي مغلق حالياً (عطلة نهاية الأسبوع). ' .
                    'سيفتح السوق يوم الأحد الساعة 23:00 GMT.',
                ],
            ],
        ], 503);
    }
}
```

## MinimumHoldingPeriodException

```php
<?php
// app/Exceptions/MinimumHoldingPeriodException.php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class MinimumHoldingPeriodException extends Exception
{
    public function __construct(
        public readonly float $hoursRemaining,
    ) {
        parent::__construct(
            message: 'يجب الانتظار ' . $hoursRemaining . ' ساعات قبل البيع (الحد الأدنى 24 ساعة)'
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'فترة الاحتفاظ الدنيا لم تنته',
            'errors'  => [
                'holding_period' => [
                    'يجب الاحتفاظ بالذهب/الفضة لمدة 24 ساعة على الأقل قبل البيع. ' .
                    'المتبقي: ' . number_format($this->hoursRemaining, 1) . ' ساعة.',
                ],
            ],
        ], 422);
    }
}
```

## SpreadTooHighException

```php
<?php
// app/Exceptions/SpreadTooHighException.php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class SpreadTooHighException extends Exception
{
    public function __construct(
        public readonly float $spreadPercent,
    ) {
        parent::__construct(
            message: 'هامش السبريد مرتفع جداً: ' . $spreadPercent . '% (الحد الأقصى 5%)'
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'هامش السبريد مرتفع جداً',
            'errors'  => [
                'spread' => [
                    'هامش الفرق بين سعر الشراء والبيع مرتفع بشكل غير طبيعي. ' .
                    'يرجى المحاولة لاحقاً.',
                ],
            ],
        ], 502);
    }
}
```

## WalletNotActiveException

```php
<?php
// app/Exceptions/WalletNotActiveException.php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class WalletNotActiveException extends Exception
{
    public function __construct(string $message = 'المحفظة غير نشطة')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 422);
    }
}
```

## معالج الاستثناءات العام (Handler)

```php
<?php
// bootstrap/app.php (Laravel 11) أو app/Exceptions/Handler.php

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (InsufficientBalanceException $e) {
        return $e->render();
    });

    $exceptions->render(function (InsufficientHoldingException $e) {
        return $e->render();
    });

    $exceptions->render(function (PriceExpiredException $e) {
        return $e->render();
    });

    $exceptions->render(function (MarketClosedException $e) {
        return $e->render();
    });

    $exceptions->render(function (MinimumHoldingPeriodException $e) {
        return $e->render();
    });

    $exceptions->render(function (SpreadTooHighException $e) {
        return $e->render();
    });
});
```

## ملخص مع رموز الحالة

| الاستثناء | HTTP Status | متى يحدث |
|-----------|-------------|----------|
| InsufficientBalanceException | 422 | رصيد المحفظة أقل من المبلغ المطلوب |
| InsufficientHoldingException | 422 | رصيد الجرامات أقل من المطلوب للبيع |
| PriceExpiredException | 422 | مضى أكثر من 30 ثانية على جلب السعر |
| MarketClosedException | 503 | عطلة نهاية الأسبوع (الجمعة 22:00 - الأحد 23:00 GMT) |
| MinimumHoldingPeriodException | 422 | أقل من 24 ساعة على آخر عملية شراء |
| SpreadTooHighException | 502 | الفرق بين bid/ask أكبر من 5% |
| WalletNotActiveException | 422 | محفظة المستخدم غير نشطة |
