# 13 - معالجة الاستثناءات (Exception Handling)

## الاستثناءات (Exceptions)
```php
<?php
namespace AppExceptions;
use Exception;

class PaymentLinkExpiredException extends Exception {
    public function render(): JsonResponse {
        return response()->json(['success' => false, 'message' => 'رابط الدفع منتهي الصلاحية'], 410);
    }
}
class InsufficientMerchantBalanceException extends Exception {
    public function render(): JsonResponse {
        return response()->json(['success' => false, 'message' => 'رصيد التاجر غير كافٍ لإنشاء رابط الدفع'], 422);
    }
}
class PaymentLinkAlreadyUsedException extends Exception {
    public function render(): JsonResponse {
        return response()->json(['success' => false, 'message' => 'رابط الدفع مستخدم مسبقاً'], 400);
    }
}
class PaymentGatewayLockedException extends Exception {
    public function render(): JsonResponse {
        return response()->json(['success' => false, 'message' => 'تم قفل بوابة الدفع مؤقتاً لكثرة المحاولات'], 429);
    }
}
class InvalidPaymentAmountException extends Exception {
    public function render(): JsonResponse {
        return response()->json(['success' => false, 'message' => 'المبلغ المطلوب غير صالح'], 422);
    }
}
```

## جدول الاستثناءات
| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 410 | PaymentLinkExpiredException | رابط الدفع منتهي الصلاحية |
| 422 | InsufficientMerchantBalanceException | رصيد التاجر غير كافٍ |
| 400 | PaymentLinkAlreadyUsedException | رابط الدفع مستخدم مسبقاً |
| 429 | PaymentGatewayLockedException | تم قفل بوابة الدفع مؤقتاً |
| 422 | InvalidPaymentAmountException | المبلغ المطلوب غير صالح |
