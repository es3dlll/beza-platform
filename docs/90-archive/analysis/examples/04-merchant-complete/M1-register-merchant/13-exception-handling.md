# 13 - معالجة الاستثناءات (Exception Handling)

```php
<?php
namespace AppExceptions;
use Exception;

class MerchantAlreadyExistsException extends Exception {
    public function render(): IlluminateHttpJsonResponse {
        return response()->json(['success' => false, 'message' => $this->getMessage() ?: 'لديك حساب تاجر بالفعل'], 422);
    }
}
class DocumentUploadFailedException extends Exception {
    public function render(): IlluminateHttpJsonResponse {
        return response()->json(['success' => false, 'message' => 'فشل رفع المستندات'], 422);
    }
}
```

## جدول الاستثناءات
| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 422 | MerchantAlreadyExistsException | لديك حساب تاجر بالفعل |
| 422 | DocumentUploadFailedException | فشل رفع المستندات |
| 404 | ModelNotFoundException | التاجر غير موجود |
| 429 | ThrottleRequestsException | طلبات كثيرة |

## معالجة الاستثناءات في Handler
تتم معالجة هذه الاستثناءات تلقائياً عبر метод render() المضمن في كل كلاس استثناء.
