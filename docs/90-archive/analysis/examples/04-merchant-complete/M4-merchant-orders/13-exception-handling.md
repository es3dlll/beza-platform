# 13 - معالجة الاستثناءات (Exception Handling)

## الاستثناءات المخصصة (Custom Exceptions)

```php
<?php
namespace App\Exceptions;
use Exception;
use Illuminate\Http\JsonResponse;

class OrderNotFoundException extends Exception
{
    public function __construct(int $orderId)
    {
        parent::__construct("الطلب رقم {$orderId} غير موجود");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'ORDER_NOT_FOUND',
        ], 404);
    }
}
```

```php
class InvalidOrderStatusTransitionException extends Exception
{
    public function __construct(string $from, string $to)
    {
        parent::__construct("لا يمكن تغيير حالة الطلب من {$from} إلى {$to}");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'INVALID_STATUS_TRANSITION',
            'data'    => [
                'current_status' => $from ?? 'unknown',
                'requested_status' => $to ?? 'unknown',
                'valid_transitions' => OrderStatus::TRANSITIONS[$from] ?? [],
            ],
        ], 422);
    }
}
```

```php
class InsufficientStockException extends Exception
{
    public function __construct(string $productName = '', int $available = 0, int $requested = 0)
    {
        $msg = 'المخزون غير كافٍ';
        if ($productName) {
            $msg .= " للمنتج {$productName} (المتوفر: {$available}، المطلوب: {$requested})";
        }
        parent::__construct($msg);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'INSUFFICIENT_STOCK',
        ], 422);
    }
}
```

```php
class OrderCancellationTimeExpiredException extends Exception
{
    public function __construct(string $currentStatus)
    {
        parent::__construct("لا يمكن إلغاء الطلب في حالة {$currentStatus}. الإلغاء مسموح فقط للطلبات المعلقة أو المؤكدة");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'CANCELLATION_NOT_ALLOWED',
        ], 422);
    }
}
```

```php
class DuplicatePaymentWebhookException extends Exception
{
    public function __construct(string $transactionId)
    {
        parent::__construct("تمت معالجة إشعار الدفع للمعاملة {$transactionId} مسبقاً");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'DUPLICATE_PAYMENT',
        ], 409);
    }
}
```

```php
class OrderReturnWindowExpiredException extends Exception
{
    public function __construct(int $daysPassed)
    {
        parent::__construct("لا يمكن إرجاع الطلب بعد مرور {$daysPassed} يوماً من التوصيل. فترة الإرجاع 14 يوماً فقط");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code'    => 'RETURN_WINDOW_EXPIRED',
        ], 422);
    }
}
```

## معالج الاستثناءات العام (Handler)
```php
// في App\Exceptions\Handler
public function register(): void
{
    $this->renderable(function (OrderNotFoundException $e) {
        return $e->render();
    });

    $this->renderable(function (InvalidOrderStatusTransitionException $e) {
        return $e->render();
    });

    $this->renderable(function (InsufficientStockException $e) {
        return $e->render();
    });

    $this->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        if (str_contains($e->getModel(), 'Order')) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
                'code'    => 'ORDER_NOT_FOUND',
            ], 404);
        }
    });
}
```

## جدول الاستثناءات
| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 404 | OrderNotFoundException | الطلب غير موجود |
| 422 | InvalidOrderStatusTransitionException | لا يمكن تغيير حالة الطلب من X إلى Y |
| 422 | InsufficientStockException | المخزون غير كافٍ للمنتج X |
| 422 | OrderCancellationTimeExpiredException | لا يمكن إلغاء الطلب في حالة X |
| 409 | DuplicatePaymentWebhookException | تمت معالجة إشعار الدفع مسبقاً |
| 422 | OrderReturnWindowExpiredException | انتهت فترة الإرجاع (14 يوماً) |
| 403 | UnauthorizedOrderAccessException | لا تملك صلاحية الوصول لهذا الطلب |

كل استثناء يعيد استجابة JSON موحدة مع `success`, `message`, `code` لتسهيل المعالجة في التطبيق الأمامي (Flutter/React).
