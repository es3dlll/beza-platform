# 13 - معالجة الاستثناءات (Exception Handling)

## الاستثناءات (Exceptions)
```php
<?php
namespace AppExceptions;
use Exception;

class ProductNotFoundException extends Exception {
    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'المنتج غير موجود'], 404); }
}
class ProductNotBelongToMerchantException extends Exception {
    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'هذا المنتج لا يتبع لمتجرك'], 403); }
}
class ProductImageUploadFailedException extends Exception {
    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'فشل رفع صورة المنتج'], 422); }
}
class MaxProductsReachedException extends Exception {
    public function render(): JsonResponse { return response()->json(['success' => false, 'message' => 'تم الوصول للحد الأقصى من المنتجات'], 422); }
}
```

## جدول الاستثناءات
| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 404 | ProductNotFoundException | المنتج غير موجود |
| 403 | ProductNotBelongToMerchantException | هذا المنتج لا يتبع لمتجرك |
| 422 | ProductImageUploadFailedException | فشل رفع صورة المنتج |
| 422 | MaxProductsReachedException | تم الوصول للحد الأقصى من المنتجات |

## معالجة في Handler
يمكن إضافة معالجة عامة في App\Exceptions\Handler لإرجاع رسائل موحدة.
